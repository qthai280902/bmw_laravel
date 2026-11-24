<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActiveAccount()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Tài khoản nhân sự của bạn đã bị tạm khóa. Vui lòng liên hệ Top Super Admin.',
                ])
                ->with('error', 'Tài khoản nhân sự của bạn đã bị tạm khóa.');
        }

        if (! $user?->isAdmin()) {
            abort(403, 'Unauthorized access to BMW Admin Portal.');
        }

        return $next($request);
    }
}
