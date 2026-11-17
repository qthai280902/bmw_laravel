<?php

namespace App\Notifications\Admin;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAccessoryOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public AccessoryOrder $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('product');

        $searchTerm = $this->order->customer_phone ?: ('#'.$this->order->id);

        return [
            'kind' => 'accessory_order',
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'customer_phone' => $this->order->customer_phone,
            'product_name' => $this->order->product?->name ?? 'Phụ kiện chính hãng BMW',
            'quantity' => (int) $this->order->quantity,
            'total_amount' => (int) $this->order->total_amount,
            'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
            'action_url' => route('admin.accessory-orders.index', ['search' => $searchTerm], false),
        ];
    }
}
