<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
            tags: ['promotion-campaign'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.promotions.offer',
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
