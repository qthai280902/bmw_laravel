<?php

namespace App\Providers;

use App\Listeners\MergeSessionCartOnLogin;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
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
        // Event Listener: Merge session cart into DB on login
        Event::listen(Login::class, MergeSessionCartOnLogin::class);

        // Task Scheduling: Expire orders every 5 minutes
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('orders:expire')->everyFiveMinutes();
        });

        // Global View Data: Share cart count
        view()->composer('*', function ($view) {
            $view->with('cartCount', app(CartService::class)->getCount());
        });
    }
}
