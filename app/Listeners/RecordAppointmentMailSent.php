<?php

namespace App\Listeners;

use App\Models\Appointment;
use Illuminate\Mail\Events\MessageSent;

class RecordAppointmentMailSent
{
    public function handle(MessageSent $event): void
    {
        $originalMessage = $event->sent->getOriginalMessage();

        if (! method_exists($originalMessage, 'getHeaders')) {
            return;
        }

        $headers = $originalMessage->getHeaders();
        $mailKind = $headers->get('X-BMW-Mail-Kind')?->getBodyAsString();
        $appointmentId = (int) ($headers->get('X-BMW-Appointment-ID')?->getBodyAsString() ?? 0);

        if ($mailKind !== 'appointment_confirmation' || $appointmentId <= 0) {
            return;
        }

        Appointment::query()
            ->whereKey($appointmentId)
            ->update([
                'confirmation_email_sent_at' => now(),
            ]);
    }
}
