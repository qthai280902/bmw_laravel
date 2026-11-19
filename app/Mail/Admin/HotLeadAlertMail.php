<?php

namespace App\Mail\Admin;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class HotLeadAlertMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product', 'user']);

        $typeLabel = $this->appointment->type instanceof AppointmentType
            ? $this->appointment->type->label()
            : 'Lịch hẹn';
        $customerName = $this->appointment->user?->name
            ?? $this->appointment->guest_name
            ?? 'Khách hàng';

        return new Envelope(
            subject: "[HOT LEAD] Yêu cầu {$typeLabel} khẩn từ {$customerName} — #{$this->appointment->id}",
            tags: ['hot-lead'],
            metadata: [
                'appointment_id' => (string) $this->appointment->id,
                'lead_priority' => (string) ($this->appointment->lead_priority ?? Appointment::LEAD_PRIORITY_HOT),
            ],
            using: [
                function (Email $message): void {
                    $message->priority(Email::PRIORITY_HIGH);
                },
            ],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product', 'user']);

        return new Content(
            view: 'emails.admin.hot-lead-alert',
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
