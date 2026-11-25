<?php

namespace App\Mail;

use App\Mail\Customer\AppointmentConfirmationMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class CustomerPromotionMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $customerName,
        public string $vehicleName,
        public string $offerTitle,
        public string $offerContent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[BMW Showroom] {$this->offerTitle}",
            replyTo: [AppointmentConfirmationMail::salesReplyToAddress()],
            tags: ['promotion-campaign'],
            using: [AppointmentConfirmationMail::deduplicateReplyToUsing()],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.promotions.offer',
            text: 'mail.promotions.offer-text',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'promotion_campaign',
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
