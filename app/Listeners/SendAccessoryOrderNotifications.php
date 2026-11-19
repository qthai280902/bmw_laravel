<?php

namespace App\Listeners;

use App\Events\AccessoryOrderCreated;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAccessoryOrderNotifications implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(AccessoryOrderCreated $event): void
    {
        $order = $event->order->loadMissing('product');

        if (! empty($order->customer_email)) {
            try {
                Mail::to($order->customer_email)->send(new AccessoryOrderConfirmationMail($order));
            } catch (Throwable $exception) {
                Log::warning('Failed sending accessory order confirmation email.', [
                    'order_id' => $order->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        try {
            Mail::to(AdminNotificationDispatcher::showroomAdminEmails())
                ->send(new AccessoryOrderAlertMail($order));
        } catch (Throwable $exception) {
            Log::warning('Failed sending admin accessory order alert email.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
