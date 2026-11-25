<?php

namespace App\Mail;

use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);
        $refCode = $this->appointment->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Nhắc Lịch] Còn 24 giờ tới lịch hẹn tại Showroom — Mã {$refCode}",
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            using: [AppointmentConfirmationMail::deduplicateReplyToUsing()],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        return new Content(
            view: 'mail.appointments.reminder',
            text: 'mail.appointments.reminder-text',
            with: [
                'inlineImagePath' => $this->appointment->product?->localImagePath(),
                'fallbackBadgePng' => AppointmentConfirmationMail::fallbackBadgePngBytes(),
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'appointment_reminder',
                'X-BMW-Appointment-ID' => (string) $this->appointment->id,
            ],
        );
    }

    /**
     * @return array<int, Attachment|Attachable>
     */
    public function attachments(): array
    {
        $this->appointment->loadMissing(['product.category', 'user', 'assignedSales']);

        return [$this->appointment];
    }
}
