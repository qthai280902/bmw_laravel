<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product', 'user']);
        $typeLabel = $this->appointment->type?->label() ?? 'Lịch hẹn';

        return new Envelope(
            subject: "[BMW Showroom] Nhắc lịch {$typeLabel} sắp diễn ra — Mã hẹn #{$this->appointment->id}",
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product', 'user']);

        return new Content(
            view: 'mail.appointments.reminder',
        );
    }

    /**
     * @return array<int, Attachment|Attachable>
     */
    public function attachments(): array
    {
        return [$this->appointment];
    }
}
