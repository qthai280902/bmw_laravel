<?php

namespace App\Models\Concerns;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCrmArchiveAndAttribution
{
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function scopeActiveRecords(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('archived_at'));
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('archived_at'));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function canArchive(): bool
    {
        return $this->isTerminalStatus() && ! $this->isArchived();
    }

    public function archive(?User $actor = null): void
    {
        if (! $this->isTerminalStatus()) {
            throw InvalidStatusTransitionException::forTransition(
                $this->statusLabel(),
                'Kho lưu trữ',
                'Chỉ cho phép lưu trữ hồ sơ đã hoàn tất hoặc đã hủy.'
            );
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: $actor ? InteractionTimelineRecorder::ACTOR_ADMIN : InteractionTimelineRecorder::ACTOR_SYSTEM,
            actorLabel: $actor ? InteractionTimelineRecorder::adminActorLabel($actor) : 'Hệ thống tự động',
            title: 'Đưa hồ sơ vào Kho lưu trữ',
            detail: 'Hồ sơ đã đóng được chuyển vào Kho lưu trữ CRM.',
            channel: 'CRM',
        );

        $previousTimestamps = $this->timestamps;
        $this->timestamps = false;

        try {
            $this->forceFill([
                'archived_at' => now(),
                'archived_by' => $actor?->id,
                'meta_data' => $meta,
            ])->saveQuietly();
        } finally {
            $this->timestamps = $previousTimestamps;
        }

        app(ShowroomCacheService::class)->flushDashboard();
    }

    public function unarchive(?User $actor = null): void
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
            actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
            title: 'Khôi phục hồ sơ từ Kho lưu trữ',
            detail: 'Hồ sơ đã được đưa trở lại danh sách CRM đang hoạt động.',
            channel: 'CRM',
        );

        $previousTimestamps = $this->timestamps;
        $this->timestamps = false;

        try {
            $this->forceFill([
                'archived_at' => null,
                'archived_by' => null,
                'meta_data' => $meta,
            ])->saveQuietly();
        } finally {
            $this->timestamps = $previousTimestamps;
        }

        app(ShowroomCacheService::class)->flushDashboard();
    }

    public function cancellationAttributionLabel(): ?string
    {
        $statusValue = $this->status instanceof \BackedEnum
            ? (string) $this->status->value
            : (string) $this->status;

        return InteractionTimelineRecorder::cancellationAttributionLabel($this->meta_data, $statusValue);
    }
}
