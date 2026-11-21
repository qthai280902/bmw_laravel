<?php

namespace App\Console\Commands;

use App\Services\Cache\ShowroomCacheService;
use Illuminate\Console\Command;

use function Laravel\Prompts\callout;
use function Laravel\Prompts\table;

class ShowroomCacheClearCommand extends Command
{
    protected $signature = 'showroom:cache-clear';

    protected $description = 'Xóa toàn bộ bộ nhớ đệm nghiệp vụ BMW Showroom (danh mục, bài viết, tổng quan CRM, danh sách Sales).';

    public function handle(ShowroomCacheService $cacheService): int
    {
        $cacheService->flushCatalog();
        $cacheService->flushArticles();
        $cacheService->flushDashboard();
        $cacheService->flushSalesRoster();

        $rows = [
            ['Catalog & Featured Products', 'Đã làm mới'],
            ['Editorial Articles Showcase', 'Đã làm mới'],
            ['Admin CRM Dashboard Summary', 'Đã làm mới'],
            ['Sales Roster Cache', 'Đã làm mới'],
        ];

        if ($this->input->isInteractive()) {
            table(['Phân hệ Cache', 'Trạng thái'], $rows);
            callout('Đã xóa và tăng phiên bản toàn bộ cache BMW Showroom.', 'Showroom Cache');
        } else {
            $this->table(['Phân hệ Cache', 'Trạng thái'], $rows);
            $this->info('Đã xóa và tăng phiên bản toàn bộ cache BMW Showroom.');
        }

        return self::SUCCESS;
    }
}
