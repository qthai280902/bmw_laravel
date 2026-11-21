<?php

namespace App\Mail\Admin;

use App\Models\AccessoryOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderAlertMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public AccessoryOrder $order) {}

    public function envelope(): Envelope
    {
        $this->order->loadMissing('product');
        $productName = $this->order->product?->name ?? 'Phụ kiện chính hãng BMW';
        $refCode = $this->order->publicReferenceCode();

        return new Envelope(
            subject: "[BMW Lifestyle] Đơn đặt mua phụ kiện mới ({$productName}) — Mã {$refCode}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.accessory-order-alert',
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
