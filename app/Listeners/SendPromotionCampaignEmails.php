<?php

namespace App\Listeners;

use App\Events\PromotionCampaignDispatched;
use App\Mail\CustomerPromotionMail;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPromotionCampaignEmails implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $uniqueFor = 300;

    public int $tries = 3;

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('gmail-campaign'),
        ];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function uniqueId(PromotionCampaignDispatched $event): string
    {
        return $event->campaignId;
    }

    public function handle(PromotionCampaignDispatched $event): void
    {
        $quotaGuard = app(GmailQuotaGuard::class);

        foreach ($event->recipients as $recipient) {
            if (! $quotaGuard->canSendMarketingEmail()) {
                $quotaGuard->notifySoftLimitReachedOnce();
                Log::warning('Promotion campaign paused because daily Gmail soft limit was reached.', [
                    'campaign_id' => $event->campaignId,
                    'today_count' => $quotaGuard->todayCount(),
                    'daily_soft_limit' => $quotaGuard->dailySoftLimit(),
                ]);

                break;
            }

            $email = trim((string) ($recipient['email'] ?? ''));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Mail::to($email)->send(new CustomerPromotionMail(
                    customerName: (string) ($recipient['name'] ?? 'Quý khách'),
                    vehicleName: (string) ($recipient['vehicle_name'] ?? 'Các dòng xe BMW'),
                    offerTitle: $event->offerTitle,
                    offerContent: $event->offerContent,
                ));
            } catch (Throwable $exception) {
                Log::warning('Failed dispatching promotion email to customer.', [
                    'campaign_id' => $event->campaignId,
                    'email' => $email,
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    public function failed(PromotionCampaignDispatched $event, Throwable $exception): void
    {
        $firstRecipient = (string) ($event->recipients[0]['email'] ?? 'danh-sach-khach-hang');
        $recipientSummary = count($event->recipients) > 1
            ? "{$firstRecipient} (+".(count($event->recipients) - 1).' người khác)'
            : $firstRecipient;

        app(AdminNotificationDispatcher::class)->notifyMailDeliveryFailure(
            recipientEmail: $recipientSummary,
            mailContext: "Chiến dịch ưu đãi: {$event->offerTitle}",
            errorMessage: $exception->getMessage() !== ''
                ? $exception->getMessage()
                : 'Lỗi gửi hàng loạt email chiến dịch khuyến mãi sau 3 lần thử.',
        );
    }
}
