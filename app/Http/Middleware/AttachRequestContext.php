<?php

namespace App\Http\Middleware;

use App\Models\AccessoryOrder;
use App\Models\Appointment;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachRequestContext
{
    /**
     * Gắn ngữ cảnh truy vết (trace_id, actor, URL, hồ sơ CRM / AI) vào Laravel Context
     * để xuyên suốt HTTP request → Log → Timeline → Queue Worker.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = trim((string) $request->header('X-Request-Trace-Id', ''));
        if ($traceId === '') {
            $traceId = Str::uuid()->toString();
        }

        $user = $request->user();

        Context::add([
            'trace_id' => $traceId,
            'url' => $request->fullUrl(),
            'actor_id' => $user?->id,
            'actor_role' => $user ? $user->normalizedRole() : 'guest',
        ]);

        // Gắn thêm ngữ cảnh hồ sơ CRM hoặc phiên AI nếu route/request có mang theo
        $route = $request->route();
        if ($route) {
            $appointment = $route->parameter('appointment');
            if ($appointment instanceof Appointment) {
                Context::add('crm_reference', $appointment->publicReferenceCode());
                Context::addHidden([
                    'crm_internal_customer_code' => $appointment->customer_internal_code,
                    'crm_voucher_code' => $appointment->daily_sequence_code,
                ]);
            }

            $accessoryOrder = $route->parameter('accessoryOrder');
            if ($accessoryOrder instanceof AccessoryOrder) {
                Context::add('crm_reference', $accessoryOrder->publicReferenceCode());
                Context::addHidden([
                    'crm_internal_customer_code' => $accessoryOrder->customer_internal_code,
                    'crm_voucher_code' => $accessoryOrder->daily_sequence_code,
                ]);
            }
        }

        $visitorId = trim((string) ($request->input('visitor_id') ?: $request->cookie('bmw_ai_visitor_id') ?: ''));
        if ($visitorId !== '') {
            Context::add('ai_visitor_hash', substr(sha1($visitorId), 0, 12));
            Context::addHidden('ai_visitor_raw_id', $visitorId);
        }

        $response = $next($request);
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }
}
