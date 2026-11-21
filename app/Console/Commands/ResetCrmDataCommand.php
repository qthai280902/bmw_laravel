<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\callout;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class ResetCrmDataCommand extends Command
{
    protected $signature = 'showroom:reset-crm-data {--force : Xác nhận xóa sạch toàn bộ dữ liệu giao dịch CRM khách hàng}';

    protected $description = 'Dọn sạch toàn bộ dữ liệu giao dịch khách hàng (lịch hẹn, đơn phụ kiện, hội thoại AI, thông báo, sổ chốt ngày, tài khoản khách) và giữ nguyên 100% nhân sự, xe, phụ kiện, bài viết, cài đặt.';

    public function handle(ShowroomCacheService $cacheService): int
    {
        $availableTargets = [
            'interaction_timelines' => 'Nhật ký tương tác (interaction_timelines)',
            'ai_chat_messages' => 'Tin nhắn hội thoại AI (ai_chat_messages)',
            'ai_chat_sessions' => 'Phiên hội thoại AI (ai_chat_sessions)',
            'notifications' => 'Thông báo chuông CRM (notifications)',
            'accounting_daily_closings' => 'Sổ chốt kế toán ngày (accounting_daily_closings)',
            'appointments' => 'Lịch hẹn & Dịch vụ chăm sóc xe (appointments)',
            'accessory_orders' => 'Đơn đặt mua phụ kiện (accessory_orders)',
            'customer_users' => 'Tài khoản khách hàng thường (users không phải @bmw.com)',
        ];

        $selectedKeys = array_keys($availableTargets);

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->warn('Cần truyền cờ --force khi chạy tự động không tương tác.');

                return self::FAILURE;
            }

            $confirmed = confirm(
                label: 'Bạn có chắc chắn muốn xóa dữ liệu giao dịch CRM khách hàng? (Nhân sự @bmw.com và kho sản phẩm được giữ nguyên 100%)',
                default: false,
            );

            if (! $confirmed) {
                warning('Đã hủy thao tác dọn dữ liệu CRM.');

                return self::FAILURE;
            }

            $selectedKeys = multiselect(
                label: 'Chọn các phân hệ dữ liệu CRM cần làm sạch:',
                options: $availableTargets,
                default: array_keys($availableTargets),
                required: 'Vui lòng chọn ít nhất một phân hệ để dọn sạch.',
            );
        }

        $summaryRows = [];

        DB::transaction(function () use ($availableTargets, $selectedKeys, &$summaryRows): void {
            foreach ($availableTargets as $table => $label) {
                if ($table === 'customer_users' || ! in_array($table, $selectedKeys, true)) {
                    continue;
                }

                if (! Schema::hasTable($table)) {
                    continue;
                }

                $count = (int) DB::table($table)->count();
                DB::table($table)->delete();
                $summaryRows[] = [$label, $count];
            }

            if (in_array('customer_users', $selectedKeys, true)) {
                // Delete regular customer accounts while strictly preserving all @bmw.com staff & configured admin emails
                $extraAdmins = User::configuredAdminEmails();
                $customerUsersQuery = User::query()
                    ->where('is_super_admin', false)
                    ->whereRaw('LOWER(email) NOT LIKE ?', ['%@bmw.com'])
                    ->when($extraAdmins !== [], function (Builder $builder) use ($extraAdmins): void {
                        $builder->whereNotIn(DB::raw('LOWER(email)'), $extraAdmins);
                    });

                $deletedCustomerUsers = (int) $customerUsersQuery->count();
                if ($deletedCustomerUsers > 0) {
                    $customerUsersQuery->delete();
                }

                $summaryRows[] = ['Tài khoản khách hàng thường đã dọn', $deletedCustomerUsers];
            }

            // Ensure top Super Admin flag on thai@bmw.com
            User::query()
                ->whereRaw('LOWER(email) = ?', ['thai@bmw.com'])
                ->update(['is_super_admin' => true]);
        });

        $cacheService->flushCatalog();
        $cacheService->flushArticles();
        $cacheService->flushDashboard();
        $cacheService->flushSalesRoster();

        if (! $this->option('force') && $this->input->isInteractive()) {
            table(['Hạng mục đã làm sạch', 'Số bản ghi đã xóa'], $summaryRows);
            callout('Đã dọn sạch các phân hệ CRM đã chọn. Tài khoản nhân sự, kho xe/phụ kiện và bài viết được bảo toàn 100%.', 'CRM Reset Completed');
        } else {
            $this->table(['Hạng mục đã làm sạch', 'Số bản ghi đã xóa'], $summaryRows);
            $this->info('Đã dọn sạch toàn bộ dữ liệu giao dịch khách hàng. Tài khoản nhân sự, kho sản phẩm, danh mục, bài viết và cài đặt hệ thống được bảo toàn 100%.');
        }

        return self::SUCCESS;
    }
}
