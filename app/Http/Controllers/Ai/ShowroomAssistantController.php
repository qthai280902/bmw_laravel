<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiConversationTracker;
use App\Services\Ai\ShowroomAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowroomAssistantController extends Controller
{
    public function __invoke(
        Request $request,
        ShowroomAssistantService $assistant,
        AiConversationTracker $tracker
    ): JsonResponse {
        $validated = $this->validateAssistantRequest($request);
        $message = $this->resolveMessage($validated, $request->hasFile('image'));

        $startedAt = microtime(true);
        $payload = $assistant->ask($message, [
            'visitor_id' => $validated['visitor_id'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'product_slug' => $validated['product_slug'] ?? null,
            'product_name' => $validated['product_name'] ?? null,
            'image' => $request->file('image'),
        ]);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $currentProduct = Arr::pull($payload, 'current_product');

        $session = $tracker->recordExchange(
            request: $request,
            message: $message,
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

    public function stream(
        Request $request,
        ShowroomAssistantService $assistant,
        AiConversationTracker $tracker
    ): StreamedResponse {
        $validated = $this->validateAssistantRequest($request);
        $message = $this->resolveMessage($validated, $request->hasFile('image'));
        $image = $request->file('image');
        $startedAt = microtime(true);

        $options = [
            'visitor_id' => $validated['visitor_id'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'product_slug' => $validated['product_slug'] ?? null,
            'product_name' => $validated['product_name'] ?? null,
            'image' => $image,
        ];

        return response()->stream(function () use ($request, $assistant, $tracker, $message, $options, $validated, $startedAt): void {
            foreach ($assistant->stream($message, $options) as $event) {
                $eventName = $event['event'] ?? 'chunk';
                $data = $event['data'] ?? [];

                if ($eventName === 'done') {
                    $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
                    $currentProduct = Arr::pull($data, 'current_product');

                    $session = $tracker->recordExchange(
                        request: $request,
                        message: $message,
                        assistantResponse: $data,
                        latencyMs: $latencyMs,
                        visitorId: $validated['visitor_id'] ?? null,
                        pageUrl: $validated['page_url'] ?? null,
                        referrer: $validated['referrer'] ?? null,
                        currentProduct: is_array($currentProduct) ? $currentProduct : null
                    );

                    if ($session) {
                        $data['conversation_id'] = $session->id;
                    }
                }

                echo 'event: '.$eventName."\n";
                echo 'data: '.(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')."\n\n";

                if (ob_get_level() > 0) {
                    @ob_flush();
                }

                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function validateAssistantRequest(Request $request): array
    {
        return $request->validate([
            'message' => ['nullable', 'required_without:image', 'string', 'max:600'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'visitor_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'product_slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/'],
            'product_name' => ['nullable', 'string', 'max:200'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveMessage(array $validated, bool $hasImage): string
    {
        $message = trim((string) ($validated['message'] ?? ''));

        if ($message === '' && $hasImage) {
            return 'Hãy nhận diện mẫu xe hoặc phụ kiện BMW trong hình ảnh này và tư vấn thông tin chính hãng tương ứng.';
        }

        return $message;
    }
}
