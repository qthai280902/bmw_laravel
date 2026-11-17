<?php

namespace App\Services\Ai;

use App\Enums\VehicleType;
use App\Http\Controllers\Ai\ShowroomAssistantController;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiConversationTracker
{
    /**
     * @param  array{answer?: string, status?: string, reason?: string, suggestions?: array<int, mixed>, current_product?: array<string, mixed>|null}  $assistantResponse
     * @param  array<string, mixed>|null  $currentProduct
     */
    public function recordExchange(
        Request $request,
        string $message,
        array $assistantResponse,
        int $latencyMs,
        ?string $visitorId = null,
        ?string $pageUrl = null,
        ?string $referrer = null,
        ?array $currentProduct = null
    ): ?AiChatSession {
        try {
            return DB::transaction(function () use ($request, $message, $assistantResponse, $latencyMs, $visitorId, $pageUrl, $referrer, $currentProduct): AiChatSession {
                $session = $this->resolveSession(
                    visitorId: $this->normalizeVisitorId($visitorId),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );

                $answer = (string) ($assistantResponse['answer'] ?? '');
                $reason = (string) ($assistantResponse['reason'] ?? $assistantResponse['status'] ?? 'ok');
                $pagePath = $this->safePath($pageUrl);
                $resolvedProduct = $currentProduct
                    ?? ($assistantResponse['current_product'] ?? null)
                    ?? $this->resolveProductFromPagePath($pagePath);

                AiChatMessage::create([
                    'ai_chat_session_id' => $session->id,
                    'role' => 'user',
                    'content' => $this->safeContent($message),
                    'content_preview' => $this->preview($message),
                    'page_url' => $this->safeUrl($pageUrl),
                    'page_path' => $pagePath,
                    'referrer' => $this->safeUrl($referrer),
                ]);

                AiChatMessage::create([
                    'ai_chat_session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => $this->safeContent($answer),
                    'content_preview' => $this->preview($answer),
                    'page_url' => $this->safeUrl($pageUrl),
                    'page_path' => $pagePath,
                    'referrer' => $this->safeUrl($referrer),
                    'provider' => (string) config('showroom_ai.provider', 'gemini'),
                    'model' => filled(config('showroom_ai.model')) ? (string) config('showroom_ai.model') : null,
                    'response_reason' => $reason,
                    'latency_ms' => max(0, $latencyMs),
                ]);

                $detectedIntent = $this->detectIntent($message, is_array($resolvedProduct) ? $resolvedProduct : null);

                $attributes = [
                    'last_seen_at' => now(),
                    'message_count' => $session->message_count + 2,
                    'last_message_preview' => $this->preview($message),
                    'last_intent' => $detectedIntent ?? $session->last_intent,
                ];

                if (is_array($resolvedProduct) && filled($resolvedProduct['id'] ?? null) && filled($resolvedProduct['name'] ?? null)) {
                    $attributes['context_product_id'] = (int) $resolvedProduct['id'];
                    $attributes['context_product_name'] = Str::limit((string) $resolvedProduct['name'], 255, '');
                    $attributes['context_product_slug'] = filled($resolvedProduct['slug'] ?? null)
                        ? Str::limit((string) $resolvedProduct['slug'], 255, '')
                        : null;
                }

                $session->forceFill($attributes)->save();

                return $session;
            });
        } catch (Throwable $exception) {
            Log::warning('Showroom AI conversation logging failed.', [
                'reason' => 'conversation_logging_failed',
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * Idempotently link an Appointment with the customer's AI chat session and compute handover summary.
     */
    public function linkAppointment(Appointment $appointment, ?Request $request = null): ?AiChatSession
    {
        try {
            return DB::transaction(function () use ($appointment, $request): ?AiChatSession {
                $existingSession = $this->findAlreadyLinkedSession('appointment', $appointment->id, $appointment->ai_conversation_id);

                if ($existingSession !== null) {
                    return $this->syncSessionAndAppointmentHandover($existingSession, $appointment, $existingSession->link_confidence ?: 'visitor_id');
                }

                $visitorId = $this->normalizeVisitorId(
                    $appointment->ai_visitor_id ?: $request?->cookie(ShowroomAssistantController::VISITOR_COOKIE_NAME)
                );
                $ipHash = $request ? $this->hashNullable($request->ip()) : null;

                $session = null;
                $confidence = 'visitor_id';

                if ($visitorId !== null) {
                    $session = AiChatSession::query()
                        ->where('visitor_id', $visitorId)
                        ->latest('last_seen_at')
                        ->first();
                } elseif ($ipHash !== null) {
                    $confidence = 'ip_recent';
                    $session = AiChatSession::query()
                        ->where('ip_hash', $ipHash)
                        ->where('last_seen_at', '>=', now()->subHours(24))
                        ->where(function ($query): void {
                            $query->whereNull('linked_source_type')
                                ->orWhere('status', '!=', AiChatSession::STATUS_CONVERTED);
                        })
                        ->latest('last_seen_at')
                        ->first();
                }

                if (! $session) {
                    return null;
                }

                return $this->syncSessionAndAppointmentHandover($session, $appointment, $confidence);
            });
        } catch (Throwable $exception) {
            Log::warning('Showroom AI appointment handover failed.', [
                'appointment_id' => $appointment->id,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * @param  array{name?: mixed, email?: mixed, phone?: mixed}  $customer
     */
    public function linkCustomerTouchpoint(
        Request $request,
        string $sourceType,
        int $sourceId,
        ?string $visitorId,
        array $customer
    ): ?AiChatSession {
        try {
            return DB::transaction(function () use ($request, $sourceType, $sourceId, $visitorId, $customer): ?AiChatSession {
                $alreadyLinked = $this->findAlreadyLinkedSession($sourceType, $sourceId);
                if ($alreadyLinked !== null) {
                    $this->applyCustomerDetailsToSession($alreadyLinked, $sourceType, $sourceId, $alreadyLinked->link_confidence ?: 'visitor_id', $customer);

                    return $alreadyLinked;
                }

                $visitorId = $this->normalizeVisitorId($visitorId ?: $request->cookie(ShowroomAssistantController::VISITOR_COOKIE_NAME));
                $ipHash = $this->hashNullable($request->ip());
                $query = AiChatSession::query();
                $confidence = 'visitor_id';

                if ($visitorId) {
                    $query->where('visitor_id', $visitorId);
                } elseif ($ipHash) {
                    $confidence = 'ip_recent';
                    $query->where('ip_hash', $ipHash)
                        ->where('last_seen_at', '>=', now()->subHours(24));
                } else {
                    return null;
                }

                $session = $query
                    ->where(function ($query): void {
                        $query->whereNull('linked_source_type')
                            ->orWhere('status', '!=', AiChatSession::STATUS_CONVERTED);
                    })
                    ->latest('last_seen_at')
                    ->first();

                if (! $session) {
                    return null;
                }

                $this->applyCustomerDetailsToSession($session, $sourceType, $sourceId, $confidence, $customer);

                return $session;
            });
        } catch (Throwable $exception) {
            Log::warning('Showroom AI conversation logging/linking failed.', [
                'reason' => 'conversation_linking_failed',
                'exception' => $exception::class,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return null;
        }
    }

    public function summarizeSession(AiChatSession $session): string
    {
        $afterId = (int) ($session->reset_after_message_id ?? 0);

        $userMessages = AiChatMessage::query()
            ->where('ai_chat_session_id', $session->id)
            ->where('role', 'user')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->get(['content']);

        if ($userMessages->isEmpty()) {
            $userMessages = AiChatMessage::query()
                ->where('ai_chat_session_id', $session->id)
                ->where('role', 'user')
                ->orderByDesc('id')
                ->limit(4)
                ->get(['content'])
                ->reverse()
                ->values();
        }

        $parts = [];

        if (filled($session->contextProductLabel())) {
            $parts[] = 'Sản phẩm: '.$session->contextProductLabel();
        }

        $questions = $userMessages
            ->map(fn (AiChatMessage $message): string => Str::limit(trim(preg_replace('/\s+/u', ' ', $this->redactContactDetails((string) $message->content)) ?? ''), 120))
            ->filter()
            ->take(4)
            ->values();

        if ($questions->isNotEmpty()) {
            $parts[] = 'Khách hỏi ('.$userMessages->count().' lượt): '.$questions->implode(' | ');
        } elseif (filled($session->last_message_preview)) {
            $parts[] = 'Nội dung gần nhất: '.Str::limit((string) $session->last_message_preview, 180);
        }

        return Str::limit(implode(' — ', $parts), 900);
    }

    private function findAlreadyLinkedSession(string $sourceType, int $sourceId, ?int $sessionId = null): ?AiChatSession
    {
        if ($sessionId !== null && $sessionId > 0) {
            $byId = AiChatSession::query()->find($sessionId);
            if ($byId !== null) {
                return $byId;
            }
        }

        return AiChatSession::query()
            ->where('linked_source_type', $sourceType)
            ->where('linked_source_id', $sourceId)
            ->latest('last_seen_at')
            ->first();
    }

    private function syncSessionAndAppointmentHandover(AiChatSession $session, Appointment $appointment, string $confidence): AiChatSession
    {
        $summary = filled($appointment->ai_chat_summary)
            ? (string) $appointment->ai_chat_summary
            : $this->summarizeSession($session);

        $appointmentUpdates = [];
        if ((int) ($appointment->ai_conversation_id ?? 0) !== $session->id) {
            $appointmentUpdates['ai_conversation_id'] = $session->id;
        }
        if ($summary !== '' && $appointment->ai_chat_summary !== $summary) {
            $appointmentUpdates['ai_chat_summary'] = $summary;
        }
        if (blank($appointment->ai_visitor_id) && filled($session->visitor_id)) {
            $appointmentUpdates['ai_visitor_id'] = $session->visitor_id;
        }

        if ($appointmentUpdates !== []) {
            $appointment->forceFill($appointmentUpdates)->saveQuietly();
        }

        $this->applyCustomerDetailsToSession($session, 'appointment', $appointment->id, $confidence, [
            'name' => $appointment->guest_name ?? $appointment->user?->name,
            'email' => $appointment->guest_email ?? $appointment->user?->email,
            'phone' => $appointment->guest_phone,
        ]);

        return $session;
    }

    /**
     * @param  array{name?: mixed, email?: mixed, phone?: mixed}  $customer
     */
    private function applyCustomerDetailsToSession(
        AiChatSession $session,
        string $sourceType,
        int $sourceId,
        string $confidence,
        array $customer
    ): void {
        $name = $this->safeCustomerValue($customer['name'] ?? null);

        $session->forceFill([
            'display_name' => $name ?: $session->display_name,
            'customer_name' => $name ?: $session->customer_name,
            'customer_email' => $this->safeCustomerValue($customer['email'] ?? null) ?: $session->customer_email,
            'customer_phone' => $this->safeCustomerValue($customer['phone'] ?? null) ?: $session->customer_phone,
            'linked_source_type' => $sourceType,
            'linked_source_id' => $sourceId,
            'link_confidence' => $confidence,
            'status' => AiChatSession::STATUS_CONVERTED,
            'last_seen_at' => now(),
        ])->save();
    }

    private function resolveSession(?string $visitorId, ?string $ipAddress, ?string $userAgent): AiChatSession
    {
        $now = now();
        $ipHash = $this->hashNullable($ipAddress);
        $userAgentHash = $this->hashNullable($userAgent);

        if ($visitorId) {
            $session = AiChatSession::query()->firstOrNew(['visitor_id' => $visitorId]);
        } else {
            $session = AiChatSession::query()
                ->whereNull('visitor_id')
                ->where('ip_hash', $ipHash)
                ->where('last_seen_at', '>=', $now->copy()->subMinutes(30))
                ->latest('last_seen_at')
                ->first() ?? new AiChatSession;
        }

        if (! $session->exists) {
            $session->first_seen_at = $now;
            $session->status = AiChatSession::STATUS_NEW;
        }

        $session->forceFill([
            'visitor_id' => $visitorId ?: $session->visitor_id,
            'ip_address' => $ipAddress,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'last_seen_at' => $now,
        ])->save();

        return $session;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveProductFromPagePath(?string $pagePath): ?array
    {
        if (! is_string($pagePath) || $pagePath === '') {
            return null;
        }

        if (preg_match('#^/catalog/([a-z0-9-]+)$#i', $pagePath, $matches) !== 1) {
            return null;
        }

        $product = Product::query()
            ->active()
            ->with('category')
            ->select(['id', 'category_id', 'name', 'slug', 'type'])
            ->where('slug', $matches[1])
            ->first();

        if (! $product) {
            return null;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'raw_type' => $product->type instanceof VehicleType ? $product->type->value : (string) $product->type,
            'is_accessory' => $product->isAccessory(),
            'category' => $product->category?->name,
        ];
    }

    private function normalizeVisitorId(?string $visitorId): ?string
    {
        $visitorId = trim((string) $visitorId);

        if ($visitorId === '' || strlen($visitorId) > 80) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9._:-]+$/', $visitorId) === 1 ? $visitorId : null;
    }

    private function hashNullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash('sha256', $value.'|'.config('app.key'));
    }

    private function preview(string $text): string
    {
        return Str::limit(
            preg_replace('/\s+/', ' ', $this->redactContactDetails($this->safeContent($text))) ?? '',
            220
        );
    }

    private function safeContent(string $text): string
    {
        return Str::limit($this->redactSecrets($text), 6000, '');
    }

    private function redactSecrets(string $text): string
    {
        return preg_replace([
            '/AIza[0-9A-Za-z_\-]{20,}/',
            '/sk-[A-Za-z0-9_\-]{20,}/',
            '/(GEMINI_API_KEYS?|OPENAI_API_KEY|APP_KEY|DB_PASSWORD)\s*=\s*[^\s]+/i',
        ], [
            '[api-key hidden]',
            '[api-key hidden]',
            '$1=[secret hidden]',
        ], $text) ?? $text;
    }

    private function redactContactDetails(string $text): string
    {
        return preg_replace([
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            '/(?:\+?84|0)(?:[\s.-]?\d){8,10}/',
        ], [
            '[email hidden]',
            '[phone hidden]',
        ], $text) ?? $text;
    }

    /**
     * @param  array<string, mixed>|null  $currentProduct
     */
    private function detectIntent(string $message, ?array $currentProduct = null): ?string
    {
        $normalized = Str::ascii(Str::lower($message));

        $intent = match (true) {
            Str::contains($normalized, ['s1000', 'motorrad', 'xe may', 'mo to', 'pkl']) => 'motorbike',
            Str::contains($normalized, ['phu kien', 'tham', 'ao khoac', 'mu bao hiem']) => 'accessory',
            Str::contains($normalized, ['so sanh', 'khac gi']) => 'compare',
            Str::contains($normalized, ['bao gia', 'gia', 'tra gop']) => 'quote',
            Str::contains($normalized, ['lai thu', 'test drive']) => 'test_drive',
            Str::contains($normalized, ['suv', 'sav', 'x5', 'x3', 'gam cao']) => 'suv',
            Str::contains($normalized, ['sedan', '330i', '530i']) => 'sedan',
            default => null,
        };

        if ($intent !== null || ! is_array($currentProduct)) {
            return $intent;
        }

        if (($currentProduct['raw_type'] ?? null) === VehicleType::MOTORBIKE->value) {
            return 'motorbike';
        }

        if (($currentProduct['is_accessory'] ?? false) === true) {
            return 'accessory';
        }

        $productHaystack = Str::ascii(Str::lower((string) (($currentProduct['category'] ?? '').' '.($currentProduct['name'] ?? ''))));

        return match (true) {
            Str::contains($productHaystack, ['sav', 'suv']) => 'suv',
            Str::contains($productHaystack, ['sedan']) => 'sedan',
            default => 'vehicle',
        };
    }

    private function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || strlen($url) > 2048 || Str::contains($url, ["\n", "\r"])) {
            return null;
        }

        return $url;
    }

    private function safePath(?string $url): ?string
    {
        $url = $this->safeUrl($url);

        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? Str::limit($path, 500, '') : null;
    }

    private function safeCustomerValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 255, '');
    }
}
