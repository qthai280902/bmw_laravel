<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    public const CATEGORY_INSTALLMENT = 'installment';

    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_AFTERSALES = 'aftersales';

    public const CATEGORY_ANNOUNCEMENT = 'announcement';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'code',
        'name',
        'category',
        'subject',
        'eyebrow',
        'heading',
        'body_content',
        'highlight_title',
        'highlight_content',
        'cta_label',
        'cta_url',
        'accent_color',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_INSTALLMENT => 'Trả góp & Lãi suất',
            self::CATEGORY_PROMOTION => 'Ưu đãi & Khuyến mãi',
            self::CATEGORY_AFTERSALES => 'Hậu mãi & Bảo dưỡng',
            self::CATEGORY_ANNOUNCEMENT => 'Thông báo Showroom',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Đang dùng',
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_ARCHIVED => 'Đã lưu trữ',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? 'Thông báo Showroom';
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? 'Đang dùng';
    }

    public function statusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300',
            self::STATUS_DRAFT => 'border-amber-500/35 bg-amber-500/15 text-amber-200',
            default => 'border-zinc-500/35 bg-white/5 text-zinc-400',
        };
    }

    public function categoryColorClass(): string
    {
        return match ($this->category) {
            self::CATEGORY_INSTALLMENT => 'border-sky-500/35 bg-sky-500/15 text-sky-300',
            self::CATEGORY_PROMOTION => 'border-[#1C69D4]/40 bg-[#1C69D4]/15 text-[#70A7FF]',
            self::CATEGORY_AFTERSALES => 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300',
            default => 'border-violet-500/35 bg-violet-500/15 text-violet-300',
        };
    }
}
