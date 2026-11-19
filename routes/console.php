<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ai:prune-conversations')
    ->dailyAt('03:30')
    ->timezone('Asia/Ho_Chi_Minh');

Schedule::command('appointments:send-reminders')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('appointments:cleanup-and-followup')
    ->hourly()
    ->between('07:00', '21:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();

Schedule::command('showroom:daily-summary')
    ->dailyAt('18:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping()
    ->emailOutputOnFailure((string) (config('mail.showroom_admins.0') ?: config('mail.showroom_admin', 'advisor@bmw.com')));

Schedule::command('ai:sync-embeddings')
    ->dailyAt('02:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::call(function (): void {
    DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays(30))
        ->delete();
})
    ->name('prune-read-admin-notifications')
    ->weeklyOn(0, '03:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')
    ->weeklyOn(0, '03:15')
    ->timezone('Asia/Ho_Chi_Minh');
