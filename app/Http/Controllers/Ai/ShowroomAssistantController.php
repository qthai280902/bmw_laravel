<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiConversationTracker;
use App\Services\Ai\ShowroomAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        $startedAt = microtime(true);
        $payload = $assistant->ask($validated['message']);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $session = $tracker->recordExchange(
            request: $request,
            message: $validated['message'],
            assistantResponse: $payload,
            latencyMs: $latencyMs,
            visitorId: $validated['visitor_id'] ?? null,
            pageUrl: $validated['page_url'] ?? null,
            referrer: $validated['referrer'] ?? null
        );

        if ($session) {
            $payload['conversation_id'] = $session->id;
        }

        return response()->json($payload);
    }
}
