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
        $this->appointment->loadMissing('user');

        $typeLabel = $this->appointment->type?->label() ?? 'Lịch hẹn';
        $customerName = $this->appointment->user?->name
            ?? $this->appointment->guest_name
            ?? 'Khách hàng';

        return new Envelope(
            subject: "[CRM NỘI BỘ · LỊCH MỚI] Yêu cầu {$typeLabel} từ {$customerName} — #{$this->appointment->id}",
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
