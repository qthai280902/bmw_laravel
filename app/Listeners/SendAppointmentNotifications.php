<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAppointmentNotifications implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(AppointmentCreated $event): void
    {
        $appointment = $event->appointment->loadMissing(['product.category', 'user']);

        $recipientEmail = $appointment->user?->email ?? $appointment->guest_email;

        if (! empty($recipientEmail)) {
            try {
                Mail::to($recipientEmail)->send(new AppointmentConfirmationMail($appointment));
            } catch (Throwable $exception) {
                Log::warning('Failed sending customer appointment confirmation email.', [
                    'appointment_id' => $appointment->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        $showroomAdmins = AdminNotificationDispatcher::showroomAdminEmails();

        try {
            Mail::to($showroomAdmins)
                ->send(new AppointmentAlertMail($appointment));
        } catch (Throwable $exception) {
            Log::warning('Failed sending admin appointment alert email.', [
                'appointment_id' => $appointment->id,
                'exception' => $exception::class,
            ]);
        }

        if ($appointment->lead_priority === Appointment::LEAD_PRIORITY_HOT) {
            $this->sendHotLeadAlert($appointment);
        }
    }

    private function sendHotLeadAlert(Appointment $appointment): void
    {
        try {
            $adminEmails = User::query()
                ->salesAndAdmins()
                ->get()
                ->filter(fn (User $user): bool => $user->canAccessCrm())
                ->pluck('email')
                ->all();

            $recipients = collect($adminEmails)
                ->merge(AdminNotificationDispatcher::showroomAdminEmails())
                ->filter(fn (mixed $email): bool => is_string($email) && trim($email) !== '')
                ->map(fn (string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values()
                ->all();

            if ($recipients === []) {
                return;
            }

            Mail::to($recipients)->send(new HotLeadAlertMail($appointment));
        } catch (Throwable $exception) {
            Log::warning('Failed sending HOT LEAD alert email.', [
                'appointment_id' => $appointment->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
