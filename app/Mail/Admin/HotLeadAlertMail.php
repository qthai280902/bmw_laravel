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
        $vehicleLabel = $this->appointment->product?->name
            ?? ($this->appointment->servicePackageLabel() ?? 'BMW Showroom');
        $refCode = $this->appointment->publicReferenceCode();

        return new Envelope(
            subject: "[HOT LEAD] Khách hàng tiềm năng đặt lịch {$typeLabel} ({$vehicleLabel}) — Mã {$refCode}",
            tags: ['hot-lead', 'internal-crm'],
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
