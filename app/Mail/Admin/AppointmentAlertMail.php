<?php

namespace App\Mail\Admin;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentAlertMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product', 'user']);

        $typeLabel = $this->appointment->type?->label() ?? 'Lịch hẹn';
        $vehicleLabel = $this->appointment->product?->name
            ?? ($this->appointment->servicePackageLabel() ?? 'BMW Showroom');
        $refCode = $this->appointment->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Showroom] Yêu cầu đặt lịch mới: {$typeLabel} ({$vehicleLabel}) — Mã {$refCode}",
            tags: ['new-appointment', 'internal-crm'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.appointment-alert',
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
