<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiConversationTracker;
use App\Services\Ai\ShowroomAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ShowroomAssistantController extends Controller
{
    public function __invoke(
        Request $request,
        ShowroomAssistantService $assistant,
        AiConversationTracker $tracker
    ): JsonResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:600'],
            'visitor_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'product_slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/'],
            'product_name' => ['nullable', 'string', 'max:200'],
        ]);

        $startedAt = microtime(true);
        $payload = $assistant->ask($validated['message'], [
            'visitor_id' => $validated['visitor_id'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'product_slug' => $validated['product_slug'] ?? null,
            'product_name' => $validated['product_name'] ?? null,
        ]);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $currentProduct = Arr::pull($payload, 'current_product');

        $session = $tracker->recordExchange(
            request: $request,
            message: $validated['message'],
            assistantResponse: $payload,
            latencyMs: $latencyMs,
            visitorId: $validated['visitor_id'] ?? null,
            pageUrl: $validated['page_url'] ?? null,
            referrer: $validated['referrer'] ?? null,
            currentProduct: is_array($currentProduct) ? $currentProduct : null
        );

        if ($session) {
            $payload['conversation_id'] = $session->id;
        }

        return response()->json($payload);
    }

    public function reset(Request $request, ShowroomAssistantService $assistant): JsonResponse
    {
        $rawVisitorId = $request->input('visitor_id');
        $visitorId = is_string($rawVisitorId) ? trim($rawVisitorId) : null;

        $assistant->resetConversation($visitorId);

        return response()->json([
            'status' => 'ok',
            'reset' => true,
        ]);
    }
}
