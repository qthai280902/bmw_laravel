<?php

namespace App\Models;

use App\Collections\AccessoryOrderCollection;
use App\Exceptions\InvalidStatusTransitionException;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Models\Concerns\HasCrmArchiveAndAttribution;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\DocumentCodeGenerator;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Inventory\AccessoryStockService;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[CollectedBy(AccessoryOrderCollection::class)]
class AccessoryOrder extends Model
{
    use HasCrmArchiveAndAttribution;
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'customer_order_code',
        'daily_sequence_code',
        'customer_internal_code',
        'product_id',
        'assigned_to',
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
        'archived_at',
        'archived_by',
    ];

    protected $appends = ['total_amount'];

    /**
     * Sensitive internal attributes hidden from JSON/array serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'customer_internal_code',
        'daily_sequence_code',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'meta_data' => 'array',
            'confirmed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (AccessoryOrder $order): void {
            app(DocumentCodeGenerator::class)->populateMissingCodes($order);
        });

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

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault([
            'name' => 'Chưa phân công',
        ]);
    }

    public function publicReferenceCode(): string
    {
        if (is_string($this->customer_order_code) && trim($this->customer_order_code) !== '') {
            return trim($this->customer_order_code);
        }

        $yymmdd = ($this->created_at ?? now())->copy()->timezone(DocumentCodeGenerator::TIMEZONE)->format('ymd');

        return "ACC-{$yymmdd}-REF".str_pad((string) ($this->id ?? 1), 2, '0', STR_PAD_LEFT);
    }

    public function internalVoucherCode(): string
    {
        if (is_string($this->daily_sequence_code) && trim($this->daily_sequence_code) !== '') {
            return trim($this->daily_sequence_code);
        }

        $yymmdd = ($this->created_at ?? now())->copy()->timezone(DocumentCodeGenerator::TIMEZONE)->format('ymd');

        return "CT-{$yymmdd}-".str_pad((string) ($this->id ?? 1), 3, '0', STR_PAD_LEFT);
    }

    public function internalCustomerCode(): string
    {
        if (is_string($this->customer_internal_code) && trim($this->customer_internal_code) !== '') {
            return trim($this->customer_internal_code);
        }

        $digits = DocumentCodeGenerator::normalizePhoneDigits($this->customer_phone);
        $lastFour = substr(str_pad($digits, 4, '0', STR_PAD_LEFT), -4);

        return "KH-{$lastFour}-01";
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

            $meta = InteractionTimelineRecorder::normalizeMetaArray($locked->meta_data);

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
                if ($previousStatus !== $status && $status === self::STATUS_CANCELLED) {
                    $meta = InteractionTimelineRecorder::withCancellationFromActor($meta, $actor);
                }

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
                $this->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'assignedSales']);
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
                Log::error('Failed sending accessory order status update email.', [
                    'order_id' => $this->id,
                    'recipient' => $this->customer_email,
                    'status' => $status,
                    'trace_id' => Context::get('trace_id'),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'trace' => substr($exception->getTraceAsString(), 0, 1200),
                ]);
            }
        }

        return $result['statusChanged'];
    }
}
