<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        // ĐƠN GIẢN HÓA: Kiểm tra email admin hoặc một thuộc tính role nếu có.
        // Ở đây chúng ta dùng logic email kết thúc bằng @bmw.com cho đơn giản.
        if (! auth()->check() || ! str_ends_with(auth()->user()->email, '@bmw.com')) {
            abort(403, 'Unauthorized access to BMW Admin Portal.');
        }

        return $next($request);
    }
}
