<?php

namespace App\Listeners;

use App\Events\PromotionCampaignDispatched;
use App\Mail\CustomerPromotionMail;
use App\Models\CustomerEmailLog;
use App\Models\CustomerInstallmentPlan;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Mail\EmailTemplateRenderer;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        $renderer = app(EmailTemplateRenderer::class);

        $recipientEmails = collect($event->recipients)
            ->map(static fn (array $r): string => mb_strtolower(trim((string) ($r['email'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $installmentPlansByEmail = $recipientEmails !== []
            ? CustomerInstallmentPlan::query()
                ->with('product:id,name')
                ->whereIn(
                    'id',
                    CustomerInstallmentPlan::query()
                        ->selectRaw('MAX(id)')
                        ->whereIn('status', [CustomerInstallmentPlan::STATUS_ACTIVE, CustomerInstallmentPlan::STATUS_OVERDUE])
                        ->whereNotNull('customer_email')
                        ->groupBy('customer_email')
                )
                ->get()
                ->keyBy(static fn (CustomerInstallmentPlan $plan): string => mb_strtolower(trim((string) $plan->customer_email)))
            : collect();

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

            $customerName = trim((string) ($recipient['name'] ?? 'Quý khách')) ?: 'Quý khách';
            $vehicleName = trim((string) ($recipient['vehicle_name'] ?? 'Các dòng xe BMW')) ?: 'Các dòng xe BMW';
            $advisorName = trim((string) ($recipient['advisor_name'] ?? ''));
            $plan = $installmentPlansByEmail->get(mb_strtolower($email));

            $variables = $renderer->buildVariables(
                customerName: $customerName,
                vehicleName: $vehicleName,
                advisorName: $advisorName !== '' ? $advisorName : null,
                installmentPlan: $plan,
            );

            $interpolatedTitle = $renderer->interpolate($event->offerTitle, $variables);
            $interpolatedContent = $renderer->interpolate($event->offerContent, $variables);

            $finalTitle = $interpolatedTitle !== '' ? $interpolatedTitle : $event->offerTitle;
            $finalContent = $interpolatedContent !== '' ? $interpolatedContent : $event->offerContent;

            try {
                Mail::to($email)->send(new CustomerPromotionMail(
                    customerName: $customerName,
                    vehicleName: $vehicleName,
                    offerTitle: $finalTitle,
                    offerContent: $finalContent,
                ));

                $recipientPhone = CustomerInstallmentPlan::normalizePhoneDigits(
                    (string) (($recipient['phone'] ?? '') ?: ($plan?->customer_phone ?? ''))
                );

                CustomerEmailLog::query()->create([
                    'customer_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                    'customer_email' => mb_strtolower($email),
                    'customer_name' => $customerName,
                    'mail_type' => CustomerEmailLog::TYPE_PROMOTION_CAMPAIGN,
                    'template_code' => null,
                    'subject' => Str::limit("[BMW Showroom] {$finalTitle}", 250, ''),
                    'body_excerpt' => Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags($finalContent))), 260),
                    'sent_by' => $event->sentBy,
                    'sent_at' => now(),
                    'meta_data' => [
                        'campaign_id' => $event->campaignId,
                        'vehicle_name' => $vehicleName,
                    ],
                ]);
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
