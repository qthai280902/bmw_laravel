<?php

namespace App\Mail\Customer;

use App\Models\AccessoryOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public AccessoryOrder $order) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'assignedSales']);
        $refCode = $this->order->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Lifestyle] Cập nhật đơn phụ kiện ({$this->order->statusLabel()}) — Mã {$refCode}",
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'assignedSales']);

        return new Content(
            view: 'emails.customer.accessory-order-status',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'accessory_order_status',
                'X-BMW-Order-ID' => (string) $this->order->id,
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
