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

    public static function normalizePhoneDigits(?string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '84')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits !== '' ? $digits : '0000000000';
    }

    public function populateMissingCodes(Appointment|AccessoryOrder $record): void
    {
        $needsCustomerCode = ! is_string($record->customer_order_code) || trim($record->customer_order_code) === '';
        $needsDailyCode = ! is_string($record->daily_sequence_code) || trim($record->daily_sequence_code) === '';

        if (! $needsCustomerCode && ! $needsDailyCode) {
            return;
        }

        $rawPhone = $record instanceof Appointment
            ? ($record->guest_phone ?? $record->user?->phone ?? null)
            : $record->customer_phone;

        $phoneDigits = self::normalizePhoneDigits($rawPhone);
        $moment = $this->resolveMoment($record->created_at ?? now());
        $datePrefix = $moment->format('d/m/Y');
        $table = $record->getTable();

        $lockKey = "crm:doc-code:{$table}:{$moment->format('Ymd')}";

        Cache::lock($lockKey, 10)->block(5, function () use ($record, $needsCustomerCode, $needsDailyCode, $phoneDigits, $datePrefix): void {
            DB::transaction(function () use ($record, $needsCustomerCode, $needsDailyCode, $phoneDigits, $datePrefix): void {
                if ($needsCustomerCode) {
                    $record->customer_order_code = $this->nextPhoneSequenceCode($record::class, $phoneDigits);
                }

                if ($needsDailyCode) {
                    $record->daily_sequence_code = $this->nextDailySequenceCode($record::class, $datePrefix);
                }
            });
        });
    }

    /**
     * @return array{appointments: int, accessory_orders: int}
     */
    public function backfillAllMissing(): array
    {
        $appointmentsUpdated = 0;
        $ordersUpdated = 0;

        Appointment::query()
            ->where(function ($query): void {
                $query->whereNull('customer_order_code')
                    ->orWhere('customer_order_code', '')
                    ->orWhereNull('daily_sequence_code')
                    ->orWhere('daily_sequence_code', '');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$appointmentsUpdated): void {
                foreach ($rows as $appointment) {
                    $this->populateMissingCodes($appointment);
                    $appointment->saveQuietly();
                    $appointmentsUpdated++;
                }
            });

        AccessoryOrder::query()
            ->where(function ($query): void {
                $query->whereNull('customer_order_code')
                    ->orWhere('customer_order_code', '')
                    ->orWhereNull('daily_sequence_code')
                    ->orWhere('daily_sequence_code', '');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$ordersUpdated): void {
                foreach ($rows as $order) {
                    $this->populateMissingCodes($order);
                    $order->saveQuietly();
                    $ordersUpdated++;
                }
            });

        return [
            'appointments' => $appointmentsUpdated,
            'accessory_orders' => $ordersUpdated,
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextPhoneSequenceCode(string $modelClass, string $phoneDigits): string
    {
        $prefix = "#{$phoneDigits}_";

        /** @var array<int, string|null> $existing */
        $existing = $modelClass::query()
            ->where('customer_order_code', 'like', $prefix.'%')
            ->pluck('customer_order_code')
            ->all();

        $maxSeq = 0;
        foreach ($existing as $code) {
            if (! is_string($code)) {
                continue;
            }
            $pos = strrpos($code, '_');
            if ($pos === false) {
                continue;
            }
            $seq = (int) substr($code, $pos + 1);
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        return $prefix.($maxSeq + 1);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function nextDailySequenceCode(string $modelClass, string $datePrefix): string
    {
        $prefix = $datePrefix.'/';

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
            $pos = strrpos($code, '/');
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
