<?php

namespace App\Mail\Customer;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostTestDriveFollowupMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);
        $vehicleLabel = $this->appointment->product?->name ?? 'BMW Showroom';
        $refCode = $this->appointment->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Concierge] Cảm ơn Quý khách & Bảng dự toán tài chính {$vehicleLabel} — Mã {$refCode}",
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            tags: ['post-test-drive-followup'],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        return new Content(
            view: 'mail.appointments.followup',
            text: 'mail.appointments.followup-text',
            with: [
                'estimate' => $this->appointment->financialEstimate(),
            ],
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
