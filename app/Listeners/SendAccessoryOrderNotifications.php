<?php

namespace App\Listeners;

use App\Events\AccessoryOrderCreated;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAccessoryOrderNotifications implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(AccessoryOrderCreated $event): void
    {
        $order = $event->order->loadMissing([
            'product.category',
            'product.primaryImage',
            'product.images',
            'assignedSales',
        ]);

        $customerEmail = trim((string) ($order->customer_email ?? ''));

        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($customerEmail)->send(new AccessoryOrderConfirmationMail($order));
            } catch (Throwable $exception) {
                Log::error('Failed sending accessory order confirmation email.', [
                    'order_id' => $order->id,
                    'recipient' => $customerEmail,
                    'trace_id' => Context::get('trace_id'),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'trace' => substr($exception->getTraceAsString(), 0, 1200),
                ]);
            }
        }

        $showroomAdmins = AdminNotificationDispatcher::showroomAdminEmails();

        try {
            Mail::to($showroomAdmins)
                ->send(new AccessoryOrderAlertMail($order));
        } catch (Throwable $exception) {
            Log::error('Failed sending admin accessory order alert email.', [
                'order_id' => $order->id,
                'recipients' => $showroomAdmins,
                'trace_id' => Context::get('trace_id'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => substr($exception->getTraceAsString(), 0, 1200),
            ]);
        }
    }
}
