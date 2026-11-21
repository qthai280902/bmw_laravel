<?php

namespace App\Console\Commands;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\DocumentCodeGenerator;
use App\Services\Crm\SalesAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class CleanAndSyncShowroomDataCommand extends Command
{
    protected $signature = 'showroom:clean-and-sync-data {--dry-run : Xem trước số lượng bản ghi sẽ xử lý mà không thay đổi database}';

    protected $description = 'Làm sạch dữ liệu rác thử nghiệm, chuẩn hóa mã kép, đảm bảo đội ngũ Sales, chia đều lead và làm mới cache Showroom.';

    public function handle(
        DocumentCodeGenerator $codeGenerator,
        SalesAssignmentService $salesAssignment,
        ShowroomCacheService $cacheService
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] Chạy kiểm thử — không ghi thay đổi vào cơ sở dữ liệu.');
        }

        // 1. Identify test garbage rows safely
        $garbageAppointmentsQuery = Appointment::query()->where(function ($query): void {
            $query->where('guest_email', 'like', '%@example.%')
                ->orWhere('guest_email', 'like', '%@test.%')
                ->orWhere('guest_email', 'like', 'test%')
                ->orWhere('guest_name', 'like', 'Test %')
                ->orWhere('guest_name', 'like', 'Demo %')
                ->orWhere('guest_name', 'like', 'Fake %')
                ->orWhere('guest_name', 'like', 'Smoke %')
                ->orWhere('guest_name', '=', 'Test');
        });

        $garbageOrdersQuery = AccessoryOrder::query()->where(function ($query): void {
            $query->where('customer_email', 'like', '%@example.%')
                ->orWhere('customer_email', 'like', '%@test.%')
                ->orWhere('customer_email', 'like', 'test%')
                ->orWhere('customer_name', 'like', 'Test %')
                ->orWhere('customer_name', 'like', 'Demo %')
                ->orWhere('customer_name', 'like', 'Fake %')
                ->orWhere('customer_name', 'like', 'Smoke %')
                ->orWhere('customer_name', '=', 'Test');
        });

        $deletedAppointments = (int) $garbageAppointmentsQuery->count();
        $deletedOrders = (int) $garbageOrdersQuery->count();

        if (! $dryRun) {
            if ($deletedAppointments > 0) {
                $garbageAppointmentsQuery->delete();
            }
            if ($deletedOrders > 0) {
                $garbageOrdersQuery->delete();
            }
        }

        // 2. Ensure Sales roster exists cleanly
        $salesSeeds = [
            ['email' => 'sales.minh@bmw.com', 'name' => 'Trần Quang Minh'],
            ['email' => 'sales.linh@bmw.com', 'name' => 'Nguyễn Thùy Linh'],
            ['email' => 'sales.duc@bmw.com', 'name' => 'Lê Anh Đức'],
        ];

        $createdSales = 0;
        if (! $dryRun) {
            foreach ($salesSeeds as $seed) {
                $existing = User::query()->where('email', $seed['email'])->first();
                if (! $existing) {
                    User::query()->create([
                        'name' => $seed['name'],
                        'email' => $seed['email'],
                        'role' => User::ROLE_SALES,
                        'password' => Hash::make('12345678'),
                        'email_verified_at' => now(),
                    ]);
                    $createdSales++;
                }
            }
            $cacheService->flushSalesRoster();
        } else {
            foreach ($salesSeeds as $seed) {
                if (! User::query()->where('email', $seed['email'])->exists()) {
                    $createdSales++;
                }
            }
        }

        // 3. Backfill dual codes & distribute unassigned leads
        $backfillCounts = ['appointments' => 0, 'accessory_orders' => 0];
        $distributedCounts = ['appointments' => 0, 'accessory_orders' => 0];

        if ($dryRun) {
            $backfillCounts['appointments'] = Appointment::query()
                ->get(['id', 'customer_order_code', 'daily_sequence_code', 'customer_internal_code'])
                ->filter(static fn (Appointment $a): bool => ! DocumentCodeGenerator::isModernReferenceCode($a->customer_order_code)
                    || ! DocumentCodeGenerator::isModernDailyCode($a->daily_sequence_code)
                    || ! DocumentCodeGenerator::isModernCustomerInternalCode($a->customer_internal_code))
                ->count();
            $backfillCounts['accessory_orders'] = AccessoryOrder::query()
                ->get(['id', 'customer_order_code', 'daily_sequence_code', 'customer_internal_code'])
                ->filter(static fn (AccessoryOrder $o): bool => ! DocumentCodeGenerator::isModernReferenceCode($o->customer_order_code)
                    || ! DocumentCodeGenerator::isModernDailyCode($o->daily_sequence_code)
                    || ! DocumentCodeGenerator::isModernCustomerInternalCode($o->customer_internal_code))
                ->count();
            $distributedCounts['appointments'] = Appointment::query()->whereNull('assigned_to')->count();
            $distributedCounts['accessory_orders'] = AccessoryOrder::query()->whereNull('assigned_to')->count();
        } else {
            $backfillCounts = $codeGenerator->backfillAllMissing(upgradeLegacy: true);
            $distributedCounts = $salesAssignment->distributeUnassignedEvenly();
        }

        // 4. Prune orphan media & flush caches
        Artisan::call('showroom:prune-orphan-media', $dryRun ? ['--dry-run' => true] : []);

        if (! $dryRun) {
            $cacheService->flushCatalog();
            $cacheService->flushArticles();
            $cacheService->flushDashboard();
            $cacheService->flushSalesRoster();
        }

        $this->table(
            ['Hạng mục', 'Số lượng'],
            [
                ['Lịch hẹn thử nghiệm đã dọn', $deletedAppointments],
                ['Đơn phụ kiện thử nghiệm đã dọn', $deletedOrders],
                ['Tài khoản Sales được khởi tạo thêm', $createdSales],
                ['Lịch hẹn được cấp Mã kép bổ sung', $backfillCounts['appointments']],
                ['Đơn phụ kiện được cấp Mã kép bổ sung', $backfillCounts['accessory_orders']],
                ['Lịch hẹn được phân bổ đều cho Sales', $distributedCounts['appointments']],
                ['Đơn phụ kiện được phân bổ đều cho Sales', $distributedCounts['accessory_orders']],
            ]
        );

        $this->info($dryRun ? 'Hoàn tất kiểm tra Dry-Run.' : 'Đã đồng bộ và làm sạch dữ liệu Showroom thành công.');

        return self::SUCCESS;
    }
}
