<?php

namespace App\Notifications\Admin;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuestRescheduledAppointmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
        public ?string $previousDateLabel,
        public string $newDateLabel,
        public ?string $reason = null,
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
        $this->appointment->loadMissing(['product', 'user']);

        $guestName = $this->appointment->user?->name
            ?? $this->appointment->guest_name
            ?? 'Khách hàng Showroom';
        $refCode = $this->appointment->publicReferenceCode();
        $typeLabel = $this->appointment->type instanceof AppointmentType
            ? $this->appointment->type->label()
            : (string) $this->appointment->type;

        $isService = $this->appointment->isServiceBooking();
        $actionRoute = $isService ? 'admin.service-bookings.index' : 'admin.appointments.index';

        return [
            'kind' => 'guest_rescheduled',
            'appointment_id' => $this->appointment->id,
            'reference_code' => $refCode,
            'guest_name' => $guestName,
            'guest_phone' => $this->appointment->guest_phone,
            'appointment_type' => $typeLabel,
            'product_name' => $this->appointment->product?->name
                ?? ($this->appointment->servicePackageLabel() ?: 'Dịch vụ BMW Showroom'),
            'previous_date_label' => $this->previousDateLabel,
            'new_date_label' => $this->newDateLabel,
            'reason' => $this->reason,
            'lead_priority' => $this->appointment->lead_priority ?? Appointment::LEAD_PRIORITY_NORMAL,
            'has_ai_chat' => (bool) ($this->appointment->ai_conversation_id || filled($this->appointment->ai_chat_summary)),
            'action_url' => route($actionRoute, ['search' => $refCode], false),
        ];
    }
}
