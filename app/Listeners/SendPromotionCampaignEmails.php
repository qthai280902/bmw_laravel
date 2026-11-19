<?php

namespace App\Listeners;

use App\Events\PromotionCampaignDispatched;
use App\Mail\CustomerPromotionMail;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPromotionCampaignEmails implements ShouldBeUnique, ShouldQueue
{
    public string $queue = 'notifications';

    public int $uniqueFor = 300;

    public int $tries = 3;

    public function uniqueId(PromotionCampaignDispatched $event): string
    {
        return $event->campaignId;
    }

    public function handle(PromotionCampaignDispatched $event): void
    {
        foreach ($event->recipients as $recipient) {
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
}
