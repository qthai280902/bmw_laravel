<?php

namespace App\Mail\Customer;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmationMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $typeLabel = $this->appointment->type?->label() ?? 'Lịch hẹn';

        return new Envelope(
            subject: "[BMW Showroom] Xác nhận yêu cầu {$typeLabel} — Mã hẹn #{$this->appointment->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.appointment-confirmation',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
