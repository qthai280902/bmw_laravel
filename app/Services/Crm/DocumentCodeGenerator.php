<?php

namespace App\Services\Crm;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentCodeGenerator
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public const SAFE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function normalizePhoneDigits(?string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '84')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits !== '' ? $digits : '0000000000';
    }

    public static function isModernReferenceCode(?string $code): bool
    {
        return is_string($code) && (bool) preg_match('/^(BMW|SRV|ACC)-\d{6}-[2-9A-HJ-NP-Z]{4}$/', trim($code));
    }

    public static function isModernDailyCode(?string $code): bool
    {
        return is_string($code) && (bool) preg_match('/^CT-\d{6}-\d{3,}$/', trim($code));
    }

    public static function isModernCustomerInternalCode(?string $code): bool
    {
        return is_string($code) && (bool) preg_match('/^KH-\d{4}-\d{2,}$/', trim($code));
    }

    public function populateMissingCodes(Appointment|AccessoryOrder $record, bool $upgradeLegacy = false): void
    {
        $needsCustomerCode = ! is_string($record->customer_order_code)
            || trim($record->customer_order_code) === ''
            || ($upgradeLegacy && ! self::isModernReferenceCode($record->customer_order_code));

        $needsDailyCode = ! is_string($record->daily_sequence_code)
            || trim($record->daily_sequence_code) === ''
            || ($upgradeLegacy && ! self::isModernDailyCode($record->daily_sequence_code));

        $needsInternalCustomerCode = ! is_string($record->customer_internal_code)
            || trim($record->customer_internal_code) === ''
            || ($upgradeLegacy && ! self::isModernCustomerInternalCode($record->customer_internal_code));

        if (! $needsCustomerCode && ! $needsDailyCode && ! $needsInternalCustomerCode) {
            return;
        }

        $rawPhone = $record instanceof Appointment
            ? ($record->guest_phone ?? $record->user?->phone ?? null)
            : $record->customer_phone;

        $phoneDigits = self::normalizePhoneDigits($rawPhone);
        $moment = $this->resolveMoment($record->created_at ?? now());
        $yymmdd = $moment->format('ymd');
        $table = $record->getTable();

        $lockKey = "crm:doc-code:{$table}:{$yymmdd}";

        Cache::lock($lockKey, 10)->block(5, function () use (
            $record,
            $needsCustomerCode,
            $needsDailyCode,
            $needsInternalCustomerCode,
            $phoneDigits,
            $yymmdd
        ): void {
            DB::transaction(function () use (
                $record,
                $needsCustomerCode,
                $needsDailyCode,
                $needsInternalCustomerCode,
                $phoneDigits,
                $yymmdd
            ): void {
                if ($needsCustomerCode) {
                    $prefix = $this->resolveReferencePrefix($record);
                    $record->customer_order_code = $this->generateUniqueReferenceCode($record::class, $prefix, $yymmdd);
                }

                if ($needsDailyCode) {
                    $record->daily_sequence_code = $this->nextDailySequenceCode($record::class, $yymmdd);
                }

                if ($needsInternalCustomerCode) {
                    $record->customer_internal_code = $this->nextCustomerInternalCode($record::class, $phoneDigits);
                }
            });
        });
    }

    /**
     * @return array{appointments: int, accessory_orders: int}
     */
    public function backfillAllMissing(bool $upgradeLegacy = true): array
    {
        $appointmentsUpdated = 0;
        $ordersUpdated = 0;

        Appointment::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$appointmentsUpdated, $upgradeLegacy): void {
                foreach ($rows as $appointment) {
                    $before = [
                        $appointment->customer_order_code,
                        $appointment->daily_sequence_code,
                        $appointment->customer_internal_code,
                    ];

                    $this->populateMissingCodes($appointment, $upgradeLegacy);

                    $after = [
                        $appointment->customer_order_code,
                        $appointment->daily_sequence_code,
                        $appointment->customer_internal_code,
                    ];

                    if ($before !== $after) {
                        $appointment->saveQuietly();
                        $appointmentsUpdated++;
                    }
                }
            });

        AccessoryOrder::query()
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$ordersUpdated, $upgradeLegacy): void {
                foreach ($rows as $order) {
                    $before = [
                        $order->customer_order_code,
                        $order->daily_sequence_code,
                        $order->customer_internal_code,
                    ];

                    $this->populateMissingCodes($order, $upgradeLegacy);

                    $after = [
                        $order->customer_order_code,
                        $order->daily_sequence_code,
                        $order->customer_internal_code,
                    ];

                    if ($before !== $after) {
                        $order->saveQuietly();
                        $ordersUpdated++;
                    }
                }
            });

        return [
            'appointments' => $appointmentsUpdated,
            'accessory_orders' => $ordersUpdated,
        ];
    }

    private function resolveReferencePrefix(Appointment|AccessoryOrder $record): string
    {
        if ($record instanceof AccessoryOrder) {
            return 'ACC';
        }

        return $record->isServiceBooking() ? 'SRV' : 'BMW';
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function generateUniqueReferenceCode(string $modelClass, string $prefix, string $yymmdd): string
    {
        $alphabet = self::SAFE_ALPHABET;
        $maxIndex = strlen($alphabet) - 1;

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $token = '';
            for ($i = 0; $i < 4; $i++) {
                $token .= $alphabet[random_int(0, $maxIndex)];
            }

            $candidate = "{$prefix}-{$yymmdd}-{$token}";

            if (! $modelClass::query()->where('customer_order_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return "{$prefix}-{$yymmdd}-".strtoupper(substr(md5((string) microtime(true)), 0, 4));
    }

    /**
     * Sinh mã chứng từ kế toán nội bộ theo ngày: CT-YYMMDD-001.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function nextDailySequenceCode(string $modelClass, string $yymmdd): string
    {
        $prefix = "CT-{$yymmdd}-";

        /** @var array<int, string|null> $existing */
        $existing = $modelClass::query()
            ->where('daily_sequence_code', 'like', $prefix.'%')
            ->pluck('daily_sequence_code')
            ->all();

        $maxSeq = 0;
        foreach ($existing as $code) {
            if (! is_string($code)) {
                continue;
            }
            $pos = strrpos($code, '-');
            if ($pos === false) {
                continue;
            }
            $seq = (int) substr($code, $pos + 1);
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        $nextSeq = $maxSeq + 1;

        return $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Sinh mã nhận diện khách hàng nội bộ đã che: KH-XXXX-NN.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function nextCustomerInternalCode(string $modelClass, string $phoneDigits): string
    {
        $lastFour = substr(str_pad($phoneDigits, 4, '0', STR_PAD_LEFT), -4);
        $prefix = "KH-{$lastFour}-";

        /** @var array<int, string|null> $existing */
        $existing = $modelClass::query()
            ->where('customer_internal_code', 'like', $prefix.'%')
            ->pluck('customer_internal_code')
            ->all();

        $maxSeq = 0;
        foreach ($existing as $code) {
            if (! is_string($code)) {
                continue;
            }
            $pos = strrpos($code, '-');
            if ($pos === false) {
                continue;
            }
            $seq = (int) substr($code, $pos + 1);
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        $nextSeq = $maxSeq + 1;

        return $prefix.str_pad((string) $nextSeq, 2, '0', STR_PAD_LEFT);
    }

    private function resolveMoment(mixed $createdAt): CarbonInterface
    {
        if ($createdAt instanceof CarbonInterface) {
            return $createdAt->copy()->timezone(self::TIMEZONE);
        }

        if (is_string($createdAt) && trim($createdAt) !== '') {
            return Carbon::parse($createdAt)->timezone(self::TIMEZONE);
        }

        return now(self::TIMEZONE);
    }
}
