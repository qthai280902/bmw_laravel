<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
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
