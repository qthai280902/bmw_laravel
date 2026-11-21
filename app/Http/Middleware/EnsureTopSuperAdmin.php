<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTopSuperAdmin
{
    /**
     * Chỉ cho phép tài khoản Super Admin cấp cao nhất (isTopSuperAdmin) truy cập phân hệ Kế toán & Sổ sách.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTopSuperAdmin()) {
            abort(403, 'Chỉ tài khoản Super Admin cấp cao nhất mới có quyền truy cập phân hệ Kế toán & Sổ sách.');
        }

        return $next($request);
    }
}
