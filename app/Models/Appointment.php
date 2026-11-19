<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\URL;

class Appointment extends Model implements Attachable
{
    use HasFactory;

    public const LEAD_PRIORITY_HOT = 'hot';

    public const LEAD_PRIORITY_WARM = 'warm';

    public const LEAD_PRIORITY_NORMAL = 'normal';

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'product_id',
        'type',
        'appointment_date',
        'status',
        'notes',
        'meta_data',
        'showroom',
        'ai_visitor_id',
        'ai_conversation_id',
        'ai_chat_summary',
        'lead_priority',
        'lead_intent',
        'ai_lead_note',
        'confirmation_email_sent_at',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'appointment_date' => 'datetime',
            'meta_data' => 'array',
            'confirmation_email_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function aiChatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_conversation_id');
    }

    public function typeLabel(): string
    {
        return $this->type instanceof AppointmentType ? $this->type->label() : 'Lịch hẹn BMW';
    }

    public function statusLabel(): string
    {
        return $this->status instanceof AppointmentStatus ? $this->status->label() : (string) $this->status;
    }

    public function getCustomerNameAttribute(): string
    {
        return (string) ($this->user?->name ?: $this->guest_name ?: 'Quý khách');
    }

    public function getCustomerPhoneAttribute(): string
    {
        return (string) ($this->guest_phone ?: $this->user?->phone ?: '');
    }

    public function getCustomerEmailAttribute(): string
    {
        return (string) ($this->user?->email ?: $this->guest_email ?: '');
    }

    public function leadPriorityLabel(): string
    {
        return match ($this->lead_priority) {
            self::LEAD_PRIORITY_HOT => 'HOT LEAD',
            self::LEAD_PRIORITY_WARM => 'TIỀM NĂNG',
            default => 'THAM KHẢO',
        };
    }

    public function leadPriorityColorClass(): string
    {
        return match ($this->lead_priority) {
            self::LEAD_PRIORITY_HOT => 'border-rose-500/40 bg-rose-500/15 text-rose-400',
            self::LEAD_PRIORITY_WARM => 'border-[#1C69D4]/40 bg-[#1C69D4]/15 text-[#70A7FF]',
            default => 'border-zinc-700 bg-zinc-900 text-zinc-400',
        };
    }

    public function leadIntentLabel(): string
    {
        return match ($this->lead_intent) {
            'ready_to_buy' => 'Sẵn sàng chốt mua',
            'test_drive_experience' => 'Trải nghiệm lái thử',
            'price_comparison' => 'So sánh & báo giá',
            'aftersales_service' => 'Dịch vụ sau bán hàng',
            default => 'Tìm hiểu thông tin',
        };
    }

    public function toMailAttachment(): Attachment
    {
        return Attachment::fromData(
            fn (): string => $this->toIcsContent(),
            "BMW-Appointment-{$this->id}.ics"
        )->withMime('text/calendar');
    }

    public function toIcsContent(): string
    {
        $this->loadMissing(['product', 'user']);

        $startAt = ($this->appointment_date ?? now()->addDay())->copy()->utc();
        $endAt = $startAt->copy()->addHour();
        $stamp = now()->utc()->format('Ymd\THis\Z');

        $typeLabel = $this->type instanceof AppointmentType ? $this->type->label() : 'Lịch hẹn BMW';
        $productName = $this->product?->name
            ?? ($this->meta_data['customer_car_model'] ?? 'Tư vấn tại BMW Showroom');
        $customerName = $this->user?->name ?? $this->guest_name ?? 'Quý khách';
        $location = $this->showroom
            ?? ($this->meta_data['showroom'] ?? 'BMW Showroom Chính Hãng Việt Nam');

        $summary = $this->escapeIcsText("BMW Showroom: {$typeLabel} - {$productName}");
        $description = $this->escapeIcsText(implode("\n", array_filter([
            "Mã lịch hẹn: #{$this->id}",
            "Khách hàng: {$customerName}",
            filled($this->guest_phone) ? "SĐT: {$this->guest_phone}" : null,
            "Dòng xe / Dịch vụ: {$productName}",
            filled($this->notes) ? "Ghi chú: {$this->notes}" : null,
        ])));
        $escapedLocation = $this->escapeIcsText((string) $location);

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BMW Showroom Vietnam//Appointments//VI',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:bmw-appointment-{$this->id}@bmw-showroom.local",
            "DTSTAMP:{$stamp}",
            'DTSTART:'.$startAt->format('Ymd\THis\Z'),
            'DTEND:'.$endAt->format('Ymd\THis\Z'),
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            "LOCATION:{$escapedLocation}",
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    /**
     * Canonical list of official BMW Showroom vehicle care & service packages.
     *
     * @return array<string, string>
     */
    public static function servicePackageOptions(): array
    {
        return [
            'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
            'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)' => 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)',
            'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu' => 'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu',
            'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng' => 'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng',
        ];
    }

    public function servicePackageLabel(): ?string
    {
        $raw = is_array($this->meta_data) ? ($this->meta_data['service_package'] ?? null) : null;

        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }

    public function temporaryManageUrl(): string
    {
        return URL::temporarySignedRoute(
            'appointments.guest.manage',
            now()->addDays(14),
            ['appointment' => $this->id]
        );
    }

    public function temporaryManageUpdateUrl(): string
    {
        return URL::temporarySignedRoute(
            'appointments.guest.update',
            now()->addDays(14),
            ['appointment' => $this->id]
        );
    }

    private function escapeIcsText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], "\n", $value);

        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $normalized
        );
    }
}
