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
        $customerName = $this->order->customer_name ?: 'Khách hàng';

        return new Envelope(
            subject: "[ĐƠN PHỤ KIỆN MỚI] Đơn hàng #{$this->order->id} từ {$customerName}",
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
