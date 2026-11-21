<?php

namespace App\Mail\Customer;

use App\Models\AccessoryOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderConfirmationMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public AccessoryOrder $order) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing('product');
        $refCode = $this->order->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Lifestyle] Đã tiếp nhận đơn phụ kiện — Mã {$refCode}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.accessory-order-confirmation',
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
