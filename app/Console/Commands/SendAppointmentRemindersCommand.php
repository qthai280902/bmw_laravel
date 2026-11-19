<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAppointmentRemindersCommand extends Command implements Isolatable
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Gửi email nhắc lịch tự động cho các lịch hẹn BMW Showroom diễn ra trong 24 giờ tới';

    public function handle(): int
    {
        $windowStart = now();
        $windowEnd = now()->copy()->addHours(24);
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        Appointment::query()
            ->with(['product.category', 'user'])
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->whereNull('reminder_sent_at')
            ->where('appointment_date', '>', $windowStart)
            ->where('appointment_date', '<=', $windowEnd)
            ->orderBy('id')
            ->chunkById(50, function (Collection $appointments) use (&$sentCount, &$skippedCount, &$failedCount): void {
                /** @var Appointment $appointment */
                foreach ($appointments as $appointment) {
                    $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

                    if ($recipientEmail === '') {
                        $skippedCount++;

                        continue;
                    }

                    try {
                        Mail::to($recipientEmail)->send(new AppointmentReminderMail($appointment));
                        $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                        $sentCount++;
                    } catch (Throwable $exception) {
                        $failedCount++;
                        Log::warning('Failed sending 24h appointment reminder email.', [
                            'appointment_id' => $appointment->id,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        $this->info("Đã gửi {$sentCount} email nhắc lịch hẹn 24h (Bỏ qua: {$skippedCount}, Lỗi: {$failedCount}).");

        return self::SUCCESS;
    }
}
