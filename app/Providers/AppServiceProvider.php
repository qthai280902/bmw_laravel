<?php

namespace App\Providers;

use App\Listeners\AdminActivitySubscriber;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Observers\AppointmentObserver;
use App\Observers\ArticleObserver;
use App\Observers\CategoryObserver;
use App\Observers\ProductImageObserver;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureHttpClientCertificateAuthority();
        $this->configureRateLimiters();

        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);
        Category::observe(CategoryObserver::class);
        Article::observe(ArticleObserver::class);
        Appointment::observe(AppointmentObserver::class);

        Event::subscribe(AdminActivitySubscriber::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('gmail-campaign', function (): Limit {
            $perMinute = max(1, (int) config('mail.campaign_rate_per_minute', 20));

            return Limit::perMinute($perMinute)->by('gmail-campaign');
        });

        RateLimiter::for('ai-concierge-chat', function (Request $request): array {
            $perMinute = max(1, (int) config('showroom.ai_chat_rate_per_minute', 15));
            $perHour = max($perMinute, (int) config('showroom.ai_chat_rate_per_hour', 60));

            $ip = (string) ($request->ip() ?: 'unknown-ip');
            $visitorId = trim((string) ($request->input('visitor_id') ?: $request->cookie('bmw_ai_visitor_id') ?: ''));
            $identity = $visitorId !== '' ? $visitorId : 'ip-only';
            $key = $identity.'|'.$ip;

            $responder = static function (Request $request, array $headers) {
                $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 60));
                $message = "Quý khách đang trao đổi với tốc độ rất nhanh. BMW Concierge xin phép nghỉ {$retryAfter} giây trước khi tiếp tục hỗ trợ Quý khách.";

                if ($request->expectsJson() || $request->is('ai/*', 'api/ai/*')) {
                    return response()->json([
                        'status' => 'rate_limited',
                        'reason' => 'rate_limited',
                        'retry_after' => $retryAfter,
                        'message' => $message,
                        'answer' => $message,
                    ], 429, $headers);
                }

                return response($message, 429, $headers);
            };

            return [
                Limit::perMinute($perMinute)->by('ai-min:'.$key)->response($responder),
                Limit::perHour($perHour)->by('ai-hour:'.$key)->response($responder),
                Limit::perMinute($perMinute * 3)->by('ai-ip-min:'.$ip)->response($responder),
            ];
        });

        $publicBookingLimiter = function (Request $request): array {
            $maxAttempts = max(1, (int) config('mail.booking_rate_limit_per_minute', 5));
            $scope = $request->routeIs('accessory-orders.*') ? 'accessory' : 'booking';
            $ip = (string) ($request->ip() ?: 'booking-ip');
            $rawPhone = (string) ($request->input('guest_phone') ?: $request->input('customer_phone') ?: '');
            $normalizedPhone = preg_replace('/\D+/', '', $rawPhone) ?: '';

            $responder = static function (Request $request, array $headers) {
                $message = 'Quý khách gửi yêu cầu quá nhanh. Vui lòng thử lại sau ít phút.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return response($message, 429, $headers);
            };

            $limits = [
                Limit::perMinute($maxAttempts)->by("{$scope}:ip:{$ip}")->response($responder),
            ];

            if ($normalizedPhone !== '') {
                $limits[] = Limit::perMinute($maxAttempts)->by("{$scope}:phone:{$ip}:{$normalizedPhone}")->response($responder);
            }

            return $limits;
        };

        RateLimiter::for('public-booking-submit', $publicBookingLimiter);
        RateLimiter::for('showroom-booking', $publicBookingLimiter);
    }

    private function configureHttpClientCertificateAuthority(): void
    {
        $configuredCertificateAuthority = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');

        if (is_string($configuredCertificateAuthority)
            && $configuredCertificateAuthority !== ''
            && is_file($configuredCertificateAuthority)) {
            return;
        }

        $certificateAuthority = collect([
            config('services.http.ca_bundle'),
            base_path('storage/certs/cacert.pem'),
            'C:\\php85\\extras\\ssl\\cacert.pem',
            'C:\\php85\\cacert.pem',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
            'C:\\Program Files\\Git\\mingw64\\etc\\ssl\\certs\\ca-bundle.crt',
            'C:\\Program Files\\Git\\mingw64\\ssl\\certs\\ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/usr/local/etc/openssl/cert.pem',
        ])->first(fn (mixed $path): bool => is_string($path) && $path !== '' && is_file($path));

        if (is_string($certificateAuthority)) {
            Http::globalOptions(['verify' => $certificateAuthority]);
        }
    }
}
