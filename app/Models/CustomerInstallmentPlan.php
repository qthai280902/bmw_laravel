<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInstallmentPlan extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_OVERDUE = 'overdue';

    public const METHOD_REDUCING_BALANCE = 'reducing_balance';

    public const METHOD_FLAT = 'flat';

    protected $fillable = [
        'customer_phone',
        'customer_email',
        'customer_name',
        'product_id',
        'appointment_id',
        'bank_name',
        'vehicle_price',
        'down_payment',
        'loan_amount',
        'monthly_payment',
        'interest_rate',
        'interest_method',
        'term_months',
        'paid_installments',
        'start_date',
        'next_due_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_price' => 'integer',
            'down_payment' => 'integer',
            'loan_amount' => 'integer',
            'monthly_payment' => 'integer',
            'interest_rate' => 'float',
            'term_months' => 'integer',
            'paid_installments' => 'integer',
            'start_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_OVERDUE]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Đang trả góp',
            self::STATUS_OVERDUE => 'Sắp / Quá hạn kỳ',
            self::STATUS_COMPLETED => 'Đã tất toán',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? 'Đang trả góp';
    }

    public function statusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300',
            self::STATUS_OVERDUE => 'border-rose-500/35 bg-rose-500/15 text-rose-300',
            default => 'border-sky-500/35 bg-sky-500/15 text-sky-300',
        };
    }

    public function progressPercent(): int
    {
        if ($this->term_months <= 0) {
            return 0;
        }

        return min(100, max(0, (int) round(($this->paid_installments / $this->term_months) * 100)));
    }

    public function remainingInstallments(): int
    {
        return max(0, $this->term_months - $this->paid_installments);
    }

    public static function normalizePhoneDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return self::canonicalizeVnPhoneDigits($digits);
    }

    /**
     * Canonicalize Vietnam phone numbers (+84 / 84 / 0084 -> leading 0) while preserving non-VN numbers.
     */
    public static function canonicalizeVnPhoneDigits(string $digits): string
    {
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0084') && strlen($digits) >= 12) {
            return '0'.substr($digits, 4);
        }

        if (str_starts_with($digits, '84') && strlen($digits) >= 10) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * @return array<string, string>
     */
    public static function interestMethodOptions(): array
    {
        return [
            self::METHOD_REDUCING_BALANCE => 'Dư nợ giảm dần',
            self::METHOD_FLAT => 'Lãi suất cố định',
        ];
    }

    public function interestMethodLabel(): string
    {
        $method = (string) ($this->interest_method ?: self::METHOD_REDUCING_BALANCE);

        return self::interestMethodOptions()[$method] ?? 'Dư nợ giảm dần';
    }
}
