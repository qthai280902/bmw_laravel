<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEmailLog extends Model
{
    public const TYPE_BOOKING_CONFIRMATION = 'booking_confirmation';

    public const TYPE_POST_VISIT_THANK_YOU = 'post_visit_thank_you';

    public const TYPE_TEMPLATED_1TO1 = 'templated_1to1';

    public const TYPE_PROMOTION_CAMPAIGN = 'promotion_campaign';

    public const TYPE_INSTALLMENT_REMINDER = 'installment_reminder';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'customer_phone',
        'customer_email',
        'customer_name',
        'mail_type',
        'template_code',
        'subject',
        'body_excerpt',
        'sent_by',
        'sent_at',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'meta_data' => AsArrayObject::class,
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_BOOKING_CONFIRMATION => 'Xác nhận đặt lịch',
            self::TYPE_POST_VISIT_THANK_YOU => 'Cảm ơn hậu mãi',
            self::TYPE_TEMPLATED_1TO1 => 'Chăm sóc 1-1',
            self::TYPE_PROMOTION_CAMPAIGN => 'Chiến dịch ưu đãi',
            self::TYPE_INSTALLMENT_REMINDER => 'Nhắc lịch trả góp',
            self::TYPE_OTHER => 'Thông báo khác',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->mail_type] ?? 'Thông báo hệ thống';
    }

    public function typeBadgeClass(): string
    {
        return match ($this->mail_type) {
            self::TYPE_BOOKING_CONFIRMATION => 'border-sky-500/35 bg-sky-500/15 text-sky-300',
            self::TYPE_POST_VISIT_THANK_YOU => 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300',
            self::TYPE_INSTALLMENT_REMINDER => 'border-amber-500/35 bg-amber-500/15 text-amber-300',
            self::TYPE_PROMOTION_CAMPAIGN => 'border-violet-500/35 bg-violet-500/15 text-violet-300',
            self::TYPE_TEMPLATED_1TO1 => 'border-[#1C69D4]/45 bg-[#1C69D4]/15 text-[#70A7FF]',
            default => 'border-white/15 bg-white/5 text-zinc-300',
        };
    }
}
