<?php

namespace App\Notifications\Admin;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Appointment|AccessoryOrder $record,
        public ?User $assignedBy = null,
    ) {}

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
        if ($this->record instanceof Appointment) {
            $this->record->loadMissing(['product', 'user']);
            $customerName = $this->record->user?->name
                ?? $this->record->guest_name
                ?? 'Khách vãng lai';
            $phone = (string) ($this->record->guest_phone ?? '');
            $codeLabel = $this->record->customer_order_code ?: '#'.$this->record->id;
            $productName = $this->record->product?->name
                ?? ($this->record->servicePackageLabel() ?: $this->record->typeLabel());
            $actionRoute = $this->record->isServiceBooking()
                ? 'admin.service-bookings.index'
                : 'admin.appointments.index';

            return [
                'kind' => 'lead_assigned',
                'record_type' => 'appointment',
                'record_id' => $this->record->id,
                'code_label' => $codeLabel,
                'daily_sequence_code' => $this->record->daily_sequence_code,
                'guest_name' => $customerName,
                'guest_phone' => $phone,
                'product_name' => $productName,
                'lead_priority' => (string) ($this->record->lead_priority ?? Appointment::LEAD_PRIORITY_NORMAL),
                'assigned_by' => $this->assignedBy?->name ?? 'Hệ thống Showroom',
                'action_url' => route($actionRoute, ['search' => $codeLabel], false),
            ];
        }

        $this->record->loadMissing('product');
        $codeLabel = $this->record->customer_order_code ?: '#ACC-'.$this->record->id;

        return [
            'kind' => 'lead_assigned',
            'record_type' => 'accessory_order',
            'record_id' => $this->record->id,
            'code_label' => $codeLabel,
            'daily_sequence_code' => $this->record->daily_sequence_code,
            'customer_name' => $this->record->customer_name,
            'customer_phone' => $this->record->customer_phone,
            'product_name' => $this->record->product?->name ?? 'Phụ kiện chính hãng BMW',
            'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
            'assigned_by' => $this->assignedBy?->name ?? 'Hệ thống Showroom',
            'action_url' => route('admin.accessory-orders.index', ['search' => $codeLabel], false),
        ];
    }
}
