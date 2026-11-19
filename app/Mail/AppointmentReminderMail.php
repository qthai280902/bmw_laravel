<?php

namespace App\Mail;

use App\Mail\Customer\AppointmentConfirmationMail;
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

        return new Envelope(
            subject: "[BMW Nhắc Lịch] Còn 24 giờ tới lịch hẹn #{$this->appointment->id} tại Showroom",
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.primaryImage', 'product.images', 'user']);

        return new Content(
            view: 'mail.appointments.reminder',
            text: 'mail.appointments.reminder-text',
            with: [
                'inlineImagePath' => $this->appointment->product?->localImagePath(),
                'fallbackBadgePng' => AppointmentConfirmationMail::fallbackBadgePngBytes(),
            ],
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
