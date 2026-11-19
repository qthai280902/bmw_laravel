<?php

namespace App\Notifications\Admin;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MailDeliveryFailureNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $recipientEmail,
        public string $mailContext,
        public string $errorMessage,
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
        return [
            'kind' => 'mail_delivery_failure',
            'recipient_email' => $this->recipientEmail,
            'mail_context' => $this->mailContext,
            'error_message' => $this->errorMessage,
            'lead_priority' => Appointment::LEAD_PRIORITY_WARM,
            'guest_name' => 'Cảnh báo hệ thống Email',
            'product_name' => $this->mailContext,
            'ai_lead_note' => $this->errorMessage,
            'action_url' => route('admin.appointments.index', [], false),
        ];
    }
}
