<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AiChatSession extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_INTERESTED = 'interested';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'visitor_id',
        'ip_address',
        'ip_hash',
        'user_agent_hash',
        'display_name',
        'customer_name',
        'customer_email',
        'customer_phone',
        'linked_source_type',
        'linked_source_id',
        'link_confidence',
        'first_seen_at',
        'last_seen_at',
        'message_count',
        'last_message_preview',
        'last_intent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'message_count' => 'integer',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class)->oldest();
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiChatMessage::class)->latestOfMany();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'Mới',
            self::STATUS_INTERESTED => 'Quan tâm',
            self::STATUS_CONVERTED => 'Đã chuyển đổi',
            self::STATUS_ARCHIVED => 'Đã lưu trữ',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return array_keys(self::statuses());
    }

    public function displayLabel(): string
    {
        if (filled($this->display_name)) {
            return (string) $this->display_name;
        }

        if (filled($this->customer_name)) {
            return (string) $this->customer_name;
        }

        if (filled($this->ip_address)) {
            return 'Khách '.$this->maskedIp();
        }

        return 'Khách chưa xác định';
    }

    public function maskedIp(): string
    {
        $ipAddress = (string) $this->ip_address;

        if ($ipAddress === '') {
            return 'ẩn danh';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $parts[3] = '*';

            return implode('.', $parts);
        }

        return Str::limit($ipAddress, 18, '...');
    }

    public function maskedVisitorId(): string
    {
        $visitorId = (string) $this->visitor_id;

        if ($visitorId === '') {
            return 'không có';
        }

        return Str::substr($visitorId, 0, 8).'...'.Str::substr($visitorId, -6);
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function statusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_INTERESTED => 'border-[#1C69D4]/25 bg-[#1C69D4]/10 text-[#70A7FF]',
            self::STATUS_CONVERTED => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
            self::STATUS_ARCHIVED => 'border-zinc-700 bg-zinc-900 text-zinc-500',
            default => 'border-yellow-500/25 bg-yellow-500/10 text-yellow-400',
        };
    }

    public function linkedSourceLabel(): string
    {
        return match ($this->linked_source_type) {
            'appointment' => 'Lịch hẹn #'.$this->linked_source_id,
            'accessory_order' => 'Đơn phụ kiện #'.$this->linked_source_id,
            default => 'Chưa liên kết',
        };
    }
}
