<?php

namespace App\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiConversationTracker
{
    /**
     * @param  array{answer?: string, status?: string, reason?: string, suggestions?: array<int, mixed>}  $assistantResponse
     */
    public function recordExchange(
        Request $request,
        string $message,
        array $assistantResponse,
        int $latencyMs,
        ?string $visitorId = null,
        ?string $pageUrl = null,
        ?string $referrer = null
    ): ?AiChatSession {
        try {
            return DB::transaction(function () use ($request, $message, $assistantResponse, $latencyMs, $visitorId, $pageUrl, $referrer): AiChatSession {
                $session = $this->resolveSession(
                    visitorId: $this->normalizeVisitorId($visitorId),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );

                $answer = (string) ($assistantResponse['answer'] ?? '');
                $reason = (string) ($assistantResponse['reason'] ?? $assistantResponse['status'] ?? 'ok');
                $pagePath = $this->safePath($pageUrl);

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

                $session->forceFill([
                    'last_seen_at' => now(),
                    'message_count' => $session->message_count + 2,
                    'last_message_preview' => $this->preview($message),
                    'last_intent' => $this->detectIntent($message),
                ])->save();

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
                $visitorId = $this->normalizeVisitorId($visitorId);
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

                return $session;
            });
        } catch (Throwable $exception) {
            Log::warning('Showroom AI conversation linking failed.', [
                'reason' => 'conversation_linking_failed',
                'exception' => $exception::class,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return null;
        }
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

    private function detectIntent(string $message): ?string
    {
        $normalized = Str::ascii(Str::lower($message));

        return match (true) {
            Str::contains($normalized, ['s1000', 'motorrad', 'xe may']) => 'motorbike',
            Str::contains($normalized, ['phu kien', 'tham', 'ao khoac', 'mu bao hiem']) => 'accessory',
            Str::contains($normalized, ['so sanh', 'khac gi']) => 'compare',
            Str::contains($normalized, ['bao gia', 'gia', 'tra gop']) => 'quote',
            Str::contains($normalized, ['lai thu', 'test drive']) => 'test_drive',
            Str::contains($normalized, ['suv', 'x5', 'x3', 'gam cao']) => 'suv',
            Str::contains($normalized, ['sedan', '330i', '530i']) => 'sedan',
            default => null,
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
