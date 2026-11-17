<?php

namespace App\Mail\Customer;

use App\Models\AccessoryOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public AccessoryOrder $order) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing('product');

        return new Envelope(
            subject: "[BMW Showroom] Cập nhật đơn phụ kiện #ACC-{$this->order->id} — {$this->order->statusLabel()}",
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('product');

        return new Content(
            view: 'emails.customer.accessory-order-status',
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
