<?php

namespace App\Mail\Customer;

use App\Models\AccessoryOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderConfirmationMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public AccessoryOrder $order) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'assignedSales']);
        $refCode = $this->order->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Lifestyle] Đã tiếp nhận đơn phụ kiện — Mã {$refCode}",
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'assignedSales']);

        return new Content(
            view: 'emails.customer.accessory-order-confirmation',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'accessory_order_confirmation',
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
