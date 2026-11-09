<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAppointmentNotifications implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(AppointmentCreated $event): void
    {
        $appointment = $event->appointment->loadMissing(['product', 'user']);

        $recipientEmail = $appointment->user?->email ?? $appointment->guest_email;

        if (! empty($recipientEmail)) {
            Mail::to($recipientEmail)->send(new AppointmentConfirmationMail($appointment));
        }

        Mail::to(config('mail.showroom_admin', 'advisor@bmw.com'))
            ->send(new AppointmentAlertMail($appointment));
    }
}
