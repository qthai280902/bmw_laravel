<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\callout;
use function Laravel\Prompts\table;

class ArchiveStaleRecordsCommand extends Command
{
    protected $signature = 'showroom:archive-stale-records
        {--days=30 : Số ngày tính từ lần cập nhật cuối để tự động đưa hồ sơ đã đóng vào Kho lưu trữ}
        {--dry-run : Xem trước số lượng bản ghi đủ điều kiện lưu trữ mà không ghi database}';

    protected $description = 'Tự động đưa các lịch hẹn, dịch vụ và đơn phụ kiện đã hoàn tất hoặc đã hủy quá 30 ngày vào Kho lưu trữ CRM.';

    public function handle(ShowroomCacheService $cacheService): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $closedAppointmentStatuses = [
            AppointmentStatus::Completed->value,
            AppointmentStatus::Cancelled->value,
        ];
        $closedOrderStatuses = [
            AccessoryOrder::STATUS_COMPLETED,
            AccessoryOrder::STATUS_CANCELLED,
        ];

        $appointmentsQuery = Appointment::query()
            ->activeRecords()
            ->whereIn('status', $closedAppointmentStatuses)
            ->where('updated_at', '<=', $cutoff);

        $ordersQuery = AccessoryOrder::query()
            ->activeRecords()
            ->whereIn('status', $closedOrderStatuses)
            ->where('updated_at', '<=', $cutoff);

        $appointmentsCount = (int) (clone $appointmentsQuery)->count();
        $ordersCount = (int) (clone $ordersQuery)->count();

        if (! $dryRun && ($appointmentsCount > 0 || $ordersCount > 0)) {
            $archivedAt = now();

            DB::transaction(function () use ($appointmentsQuery, $ordersQuery, $archivedAt): void {
                // Use direct query builder update to preserve original updated_at timestamps
                (clone $appointmentsQuery)->toBase()->update([
                    'archived_at' => $archivedAt,
                    'archived_by' => null,
                ]);

                (clone $ordersQuery)->toBase()->update([
                    'archived_at' => $archivedAt,
                    'archived_by' => null,
                ]);
            });

            $cacheService->flushDashboard();
        }

        $rows = [
            ['Lịch hẹn & Dịch vụ đủ điều kiện (quá '.$days.' ngày)', $appointmentsCount],
            ['Đơn phụ kiện đủ điều kiện (quá '.$days.' ngày)', $ordersCount],
        ];

        $summaryText = $dryRun
            ? "[DRY-RUN] Tìm thấy {$appointmentsCount} lịch hẹn/dịch vụ và {$ordersCount} đơn phụ kiện đủ điều kiện lưu trữ (chưa ghi DB)."
            : "Đã tự động lưu trữ {$appointmentsCount} lịch hẹn/dịch vụ và {$ordersCount} đơn phụ kiện quá {$days} ngày.";

        if ($this->input->isInteractive()) {
            table(['Phân hệ CRM', 'Số lượng'], $rows);
            callout($summaryText, $dryRun ? 'Archive Dry-Run' : 'CRM Archive Synced');
        } else {
            $this->table(['Phân hệ CRM', 'Số lượng'], $rows);
            $this->info($summaryText);
        }

        return self::SUCCESS;
    }
}
