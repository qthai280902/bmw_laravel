<?php

namespace App\Services\Mail;

use App\Services\Admin\AdminNotificationDispatcher;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class GmailQuotaGuard
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public const COUNTER_PREFIX = 'mail_daily_count:';

    public const ALERT_FLAG_PREFIX = 'mail_daily_soft_limit_alerted:';

    public function todayKey(?CarbonInterface $at = null): string
    {
        $date = ($at ?? now(self::TIMEZONE))->copy()->setTimezone(self::TIMEZONE)->toDateString();

        return self::COUNTER_PREFIX.$date;
    }

    public function alertFlagKey(?CarbonInterface $at = null): string
    {
        $date = ($at ?? now(self::TIMEZONE))->copy()->setTimezone(self::TIMEZONE)->toDateString();

        return self::ALERT_FLAG_PREFIX.$date;
    }

    public function dailySoftLimit(): int
    {
        return max(1, (int) config('mail.daily_soft_limit', 420));
    }

    public function todayCount(): int
    {
        return max(0, (int) Cache::get($this->todayKey(), 0));
    }

    public function recordSentMail(int $count = 1): int
    {
        $key = $this->todayKey();
        $next = $this->todayCount() + max(1, $count);
        Cache::put($key, $next, now()->addHours(48));

        return $next;
    }

    public function canSendMarketingEmail(): bool
    {
        return $this->todayCount() < $this->dailySoftLimit();
    }

    /**
     * Dispatch a CRM bell alert to Admin + Sales (excluding Marketing) once per day when soft limit is reached.
     */
    public function notifySoftLimitReachedOnce(): bool
    {
        $flagKey = $this->alertFlagKey();

        if (! Cache::add($flagKey, true, now()->addHours(30))) {
            return false;
        }

        $count = $this->todayCount();
        $limit = $this->dailySoftLimit();

        app(AdminNotificationDispatcher::class)->notifyMailDeliveryFailure(
            recipientEmail: 'chiendich-marketing@showroom',
            mailContext: 'Hạn ngạch Gmail SMTP hàng ngày',
            errorMessage: "Đã chạm ngưỡng mềm {$count}/{$limit} email/ngày. Chiến dịch khuyến mãi tự động tạm dừng để bảo toàn suất gửi thư đặt lịch & HOT LEAD.",
        );

        return true;
    }
}
