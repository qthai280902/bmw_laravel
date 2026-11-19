<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;

class CleanupAndFollowupAppointmentsCommand extends Command implements Isolatable
{
    protected $signature = 'appointments:cleanup-and-followup';

    protected $description = 'Tự động hủy lịch pending quá hạn > 24h và hẹn giờ gửi email hậu mãi sau lái thử kèm bảng dự toán trả góp';

    public function handle(): int
    {
        $cutoff = now()->copy()->subHours(24);
        $cancelledCount = 0;
        $followupScheduledCount = 0;

        // Bước 1: Dọn lịch pending quá hạn hơn 24 giờ
        Appointment::query()
            ->where('status', AppointmentStatus::Pending->value)
            ->where('appointment_date', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(50, function (Collection $appointments) use (&$cancelledCount): void {
                /** @var Appointment $appointment */
                foreach ($appointments as $appointment) {
                    $meta = is_array($appointment->meta_data) ? $appointment->meta_data : [];
                    $meta['auto_cancelled_reason'] = 'Tự động hủy do quá hạn 24 giờ chưa xác nhận';
                    $meta['auto_cancelled_at'] = now()->toIso8601String();

                    $appointment->forceFill([
                        'status' => AppointmentStatus::Cancelled->value,
                        'meta_data' => $meta,
                    ])->saveQuietly();

                    $cancelledCount++;
                }
            });

        // Bước 2: Quét bù hậu mãi sau khi hoàn thành lịch hẹn
        Appointment::query()
            ->with(['product.category', 'user'])
            ->where('status', AppointmentStatus::Completed->value)
            ->whereNull('followup_email_sent_at')
            ->orderBy('id')
            ->chunkById(50, function (Collection $appointments) use (&$followupScheduledCount): void {
                /** @var Appointment $appointment */
                foreach ($appointments as $appointment) {
                    if ($appointment->schedulePostTestDriveFollowup()) {
                        $followupScheduledCount++;
                    }
                }
            });

        $this->info("Đã tự động hủy {$cancelledCount} lịch quá hạn 24h và hẹn giờ gửi {$followupScheduledCount} email hậu mãi.");

        return self::SUCCESS;
    }
}
