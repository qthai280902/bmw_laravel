<?php

namespace App\Models;

use App\Exceptions\InvalidStatusTransitionException;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Inventory\AccessoryStockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AccessoryOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'product_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_email',
        'ai_visitor_id',
        'quantity',
        'notes',
        'status',
        'admin_notes',
        'meta_data',
        'confirmed_at',
    ];

    protected $appends = ['total_amount'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'meta_data' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            app(ShowroomCacheService::class)->flushDashboard();
        });

        static::deleted(static function (): void {
            app(ShowroomCacheService::class)->flushDashboard();
        });
    }

    public function getTotalAmountAttribute(): int
    {
        return (int) ($this->product?->price ?? 0) * max(1, (int) $this->quantity);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_COMPLETED => 'Hoàn thành',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return array_keys(self::statuses());
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    /**
     * Trả về danh sách các trạng thái kế tiếp hợp lệ theo State Machine 1 chiều.
     *
     * @return list<string>
     */
    public static function allowedTransitionsFrom(string $status): array
    {
        return match ($status) {
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED, self::STATUS_CANCELLED => [],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function allowedNextStatuses(): array
    {
        return self::allowedTransitionsFrom((string) $this->status);
    }

    public function isTerminalStatus(): bool
    {
        return in_array((string) $this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses(), true);
    }

    /**
     * Các lựa chọn trạng thái hiển thị trên giao diện quản trị (hiện tại + bước kế tiếp hợp lệ).
     *
     * @return array<string, string>
     */
    public function allowedStatusOptions(): array
    {
        $all = self::statuses();
        $current = (string) $this->status;
        $options = [
            $current => $all[$current] ?? $current,
        ];

        foreach ($this->allowedNextStatuses() as $next) {
            if (isset($all[$next])) {
                $options[$next] = $all[$next];
            }
        }

        return $options;
    }

    public function statusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'border-emerald-500/20 bg-emerald-500/5 text-emerald-500',
            self::STATUS_COMPLETED => 'border-[#1C69D4]/20 bg-[#1C69D4]/5 text-[#1C69D4]',
            self::STATUS_CANCELLED => 'border-rose-500/20 bg-rose-500/5 text-rose-500',
            default => 'border-yellow-500/20 bg-yellow-500/5 text-yellow-500',
        };
    }

    public function transitionToStatus(
        string $status,
        ?string $adminNotes = null,
        bool $updateNotes = false,
        ?User $actor = null
    ): bool {
        $result = DB::transaction(function () use ($status, $adminNotes, $updateNotes, $actor): array {
            /** @var self $locked */
            $locked = static::query()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = (string) $locked->status;
            $previousLabel = self::statuses()[$previousStatus] ?? $previousStatus;
            $nextLabel = self::statuses()[$status] ?? $status;

            if ($status !== $previousStatus && ! $locked->canTransitionTo($status)) {
                if ($locked->isTerminalStatus()) {
                    throw InvalidStatusTransitionException::forTerminalStatus($previousLabel);
                }

                throw InvalidStatusTransitionException::forTransition(
                    $previousLabel,
                    $nextLabel,
                    'Vui lòng xác nhận đơn hàng trước khi hoàn tất.'
                );
            }

            if ($previousStatus === $status && ! $updateNotes) {
                $this->setRawAttributes($locked->getAttributes(), true);
                $this->meta_data = $locked->meta_data;

                return ['statusChanged' => false, 'previousStatus' => $previousStatus, 'nextLabel' => $nextLabel];
            }

            $meta = is_array($locked->meta_data) ? $locked->meta_data : [];

            $updates = [
                'status' => $status,
            ];

            if ($updateNotes) {
                $updates['admin_notes'] = $adminNotes;
            }

            if (
                in_array($status, [self::STATUS_CONFIRMED, self::STATUS_COMPLETED], true)
                && $locked->confirmed_at === null
            ) {
                $updates['confirmed_at'] = now();
            }

            if ($previousStatus !== $status || ($updateNotes && filled($adminNotes))) {
                $detail = $previousStatus !== $status
                    ? "Cập nhật đơn #ACC-{$locked->id} từ {$previousLabel} sang {$nextLabel}."
                    : "Cập nhật ghi chú xử lý nội bộ cho đơn #ACC-{$locked->id}.";
                if ($updateNotes && filled($adminNotes)) {
                    $detail .= " Ghi chú: {$adminNotes}";
                }

                $meta = InteractionTimelineRecorder::appendToMeta(
                    meta: $meta,
                    actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
                    actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
                    title: $previousStatus !== $status ? "Chuyển trạng thái đơn: {$previousLabel} → {$nextLabel}" : 'Cập nhật ghi chú xử lý đơn phụ kiện',
                    detail: $detail,
                    channel: 'CRM',
                );
                $updates['meta_data'] = $meta;
            }

            $locked->update($updates);
            $this->setRawAttributes($locked->getAttributes(), true);
            $this->meta_data = $locked->meta_data;

            return ['statusChanged' => $previousStatus !== $status, 'previousStatus' => $previousStatus, 'nextLabel' => $nextLabel];
        });

        if ($status === self::STATUS_CANCELLED && $result['statusChanged']) {
            app(AccessoryStockService::class)->restoreStockOnCancel($this, $actor);
        }

        if ($result['statusChanged'] && filled($this->customer_email)) {
            try {
                $this->loadMissing('product');
                Mail::to($this->customer_email)->send(new AccessoryOrderStatusMail($this));

                InteractionTimelineRecorder::record(
                    model: $this,
                    actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
                    actorLabel: 'Hệ thống tự động',
                    title: "Gửi email thông báo trạng thái: {$result['nextLabel']}",
                    detail: "Hệ thống đã gửi thư cập nhật đơn #ACC-{$this->id} tới {$this->customer_email}.",
                    channel: 'Email',
                );
            } catch (Throwable $exception) {
                Log::warning('Failed sending accessory order status update email.', [
                    'order_id' => $this->id,
                    'status' => $status,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $result['statusChanged'];
    }
}
