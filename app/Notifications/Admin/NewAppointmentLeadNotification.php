<?php

namespace App\Notifications\Admin;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAppointmentLeadNotification extends Notification
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

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
            ?? 'Khách vãng lai';
        $guestEmail = $this->appointment->user?->email ?? $this->appointment->guest_email;
        $typeLabel = $this->appointment->type instanceof AppointmentType
            ? $this->appointment->type->label()
            : (string) $this->appointment->type;

        $priority = in_array(
            $this->appointment->lead_priority,
            [Appointment::LEAD_PRIORITY_HOT, Appointment::LEAD_PRIORITY_WARM, Appointment::LEAD_PRIORITY_NORMAL],
            true
        ) ? (string) $this->appointment->lead_priority : Appointment::LEAD_PRIORITY_NORMAL;

        $searchTerm = $this->appointment->guest_phone ?: $guestName;

        return [
            'kind' => 'appointment_lead',
            'appointment_id' => $this->appointment->id,
            'guest_name' => $guestName,
            'guest_phone' => $this->appointment->guest_phone,
            'guest_email' => $guestEmail,
            'appointment_type' => $typeLabel,
            'appointment_date' => $this->appointment->appointment_date?->format('d/m/Y H:i'),
            'product_name' => $this->appointment->product?->name
                ?? ($this->appointment->meta_data['customer_car_model'] ?? 'Dịch vụ chung Showroom'),
            'product_slug' => $this->appointment->product?->slug,
            'lead_priority' => $priority,
            'lead_intent' => $this->appointment->lead_intent ?? 'general_inquiry',
            'ai_lead_note' => $this->appointment->ai_lead_note,
            'has_ai_chat' => (bool) ($this->appointment->ai_conversation_id || filled($this->appointment->ai_chat_summary)),
            'ai_chat_summary' => $this->appointment->ai_chat_summary,
            'action_url' => route('admin.appointments.index', ['search' => $searchTerm], false),
        ];
    }
}
