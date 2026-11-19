<?php

namespace App\Services\Concurrency;

use Closure;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShowroomConcurrency
{
    private static string $lastDriver = 'sync';

    /**
     * Resolve the effective concurrency driver safe for tests, transactions, and host OS.
     */
    public static function resolveDriver(): string
    {
        if (app()->runningUnitTests() || DB::transactionLevel() > 0) {
            return 'sync';
        }

        $configured = strtolower(trim((string) config('showroom.concurrency_driver', 'process')));

        return in_array($configured, ['sync', 'process', 'fork'], true) ? $configured : 'sync';
    }

    /**
     * Execute independent closures concurrently with automatic sync fallback.
     *
     * IMPORTANT: Pass only `static fn ()` closures capturing primitive values (`int`/`string`/`array`),
     * resolving any needed services or Eloquent queries inside via `app(...)`.
     *
     * @param  array<int|string, Closure(): mixed>  $tasks
     * @return array<int|string, mixed>
     */
    public static function run(array $tasks): array
    {
        if ($tasks === []) {
            return [];
        }

        $driver = self::resolveDriver();

        if ($driver === 'sync') {
            self::$lastDriver = 'sync';

            return Concurrency::driver('sync')->run($tasks);
        }

        try {
            self::$lastDriver = $driver;

            return Concurrency::driver($driver)->run($tasks);
        } catch (Throwable $exception) {
            Log::warning('ShowroomConcurrency falling back to sync driver.', [
                'driver' => $driver,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            self::$lastDriver = 'sync';

            return Concurrency::driver('sync')->run($tasks);
        }
    }

    public static function lastDriverUsed(): string
    {
        return self::$lastDriver;
    }
}
