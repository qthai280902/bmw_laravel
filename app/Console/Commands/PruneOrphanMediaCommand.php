<?php

namespace App\Console\Commands;

use App\Services\Media\LocalMediaManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Storage;

class PruneOrphanMediaCommand extends Command implements Isolatable
{
    protected $signature = 'showroom:prune-orphan-media
                            {--dry-run : Chỉ liệt kê danh sách ảnh mồ côi mà không xóa file vật lý}
                            {--force-recent : Bỏ qua cửa sổ an toàn 24 giờ cho file vừa tải lên}';

    protected $description = 'Quét và dọn dẹp các tệp ảnh mồ côi trong products/ và articles/ không còn được tham chiếu trong DB';

    public function handle(LocalMediaManager $mediaManager): int
    {
        $disk = Storage::disk('public');
        $referenced = $mediaManager->collectReferencedManagedPaths();
        $cutoffTimestamp = now()->subHours(24)->getTimestamp();
        $forceRecent = (bool) $this->option('force-recent');
        $dryRun = (bool) $this->option('dry-run');

        $orphans = [];
        $skippedRecent = 0;
        $totalBytes = 0;

        foreach (LocalMediaManager::MANAGED_DIRECTORIES as $directory) {
            if (! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $file) {
                $normalized = $mediaManager->normalizeManagedPath($file);
                if ($normalized === null || isset($referenced[$normalized])) {
                    continue;
                }

                $lastModified = (int) $disk->lastModified($file);
                if (! $forceRecent && $lastModified > $cutoffTimestamp) {
                    $skippedRecent++;

                    continue;
                }

                $bytes = (int) $disk->size($file);
                $totalBytes += $bytes;
                $orphans[] = [
                    'path' => $normalized,
                    'bytes' => $bytes,
                ];
            }
        }

        $formattedSize = $this->formatBytes($totalBytes);

        if ($orphans === []) {
            $this->info("Không phát hiện ảnh mồ côi nào cần dọn (đã bỏ qua {$skippedRecent} tệp mới dưới 24 giờ).");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('[DRY RUN] Phát hiện '.count($orphans)." tệp ảnh mồ côi (ước tính giải phóng {$formattedSize}):");
            foreach ($orphans as $orphan) {
                $this->line(" - {$orphan['path']} (".$this->formatBytes($orphan['bytes']).')');
            }

            return self::SUCCESS;
        }

        $deletedCount = 0;
        foreach ($orphans as $orphan) {
            if ($disk->delete($orphan['path'])) {
                $deletedCount++;
            }
        }

        $this->info("Đã dọn {$deletedCount} tệp ảnh mồ côi, giải phóng {$formattedSize} dung lượng lưu trữ (bỏ qua {$skippedRecent} tệp mới dưới 24 giờ).");

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, '.', '').' MB';
        }

        return number_format(max(1, (int) ceil($bytes / 1024)), 0, '.', '').' KB';
    }
}
