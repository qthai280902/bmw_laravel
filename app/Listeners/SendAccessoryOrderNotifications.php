<?php

namespace App\Listeners;

use App\Events\AccessoryOrderCreated;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAccessoryOrderNotifications implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(AccessoryOrderCreated $event): void
    {
        $order = $event->order->loadMissing('product');

        if (! empty($order->customer_email)) {
            Mail::to($order->customer_email)->send(new AccessoryOrderConfirmationMail($order));
        }

        Mail::to(config('mail.showroom_admin', 'advisor@bmw.com'))
            ->send(new AccessoryOrderAlertMail($order));
    }
}
