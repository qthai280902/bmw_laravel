<?php

namespace App\Providers;

use App\Listeners\AdminActivitySubscriber;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Product;
use App\Observers\AppointmentObserver;
use App\Observers\ArticleObserver;
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

        RateLimiter::for('showroom-booking', function (Request $request): Limit {
            $maxAttempts = max(1, (int) config('mail.booking_rate_limit_per_minute', 5));

            return Limit::perMinute($maxAttempts)
                ->by((string) ($request->ip() ?: 'booking-ip'))
                ->response(function (Request $request, array $headers) {
                    $message = 'Quý khách gửi yêu cầu đặt lịch quá nhanh. Vui lòng thử lại sau ít phút.';

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    return response($message, 429, $headers);
                });
        });

        Product::observe(ProductObserver::class);
        Article::observe(ArticleObserver::class);
        Appointment::observe(AppointmentObserver::class);

        Event::subscribe(AdminActivitySubscriber::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
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
