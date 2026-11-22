<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AttachRequestContext;
use App\Http\Middleware\EnsureTopSuperAdmin;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'role' => EnsureUserHasRole::class,
            'top_super_admin' => EnsureTopSuperAdmin::class,
        ]);
        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
            AttachRequestContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
