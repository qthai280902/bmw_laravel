<?php

namespace App\Services\Ai;

use App\Models\Appointment;
use App\Models\Product;
use App\Services\Ai\Dto\ShowroomStructuredReply;
use Illuminate\Support\Str;

class StructuredOutputParser
{
    /**
     * @var list<string>
     */
    public const VALID_INTENTS = [
        'hot_lead',
        'test_drive',
        'consult',
        'service_booking',
        'accessory_inquiry',
        'general_info',
    ];

    /**
     * @var list<string>
     */
    public const VALID_LEAD_LEVELS = [
        'hot',
        'warm',
        'cold',
    ];

    /**
     * @var list<string>
     */
    public const VALID_BOOKING_TYPES = [
        'test_drive',
        'consult',
        'maintenance',
    ];

    /**
     * Gemini JSON responseSchema configuration (`responseMimeType: application/json`).
     *
     * @return array<string, mixed>
     */
    public function geminiGenerationConfig(): array
    {
        return [
            'responseMimeType' => 'application/json',
            'responseSchema' => [
                'type' => 'OBJECT',
                'properties' => [
                    'reply_markdown' => ['type' => 'STRING'],
                    'intent' => [
                        'type' => 'STRING',
                        'enum' => self::VALID_INTENTS,
                    ],
                    'lead_level' => [
                        'type' => 'STRING',
                        'enum' => self::VALID_LEAD_LEVELS,
                    ],
                    'recommended_product_ids' => [
                        'type' => 'ARRAY',
                        'items' => ['type' => 'INTEGER'],
                    ],
                    'recommended_service_package' => [
                        'type' => 'STRING',
                        'nullable' => true,
                    ],
                    'prefill_booking' => [
                        'type' => 'OBJECT',
                        'nullable' => true,
                        'properties' => [
                            'type' => [
                                'type' => 'STRING',
                                'enum' => self::VALID_BOOKING_TYPES,
                            ],
                            'product_id' => ['type' => 'INTEGER', 'nullable' => true],
                            'service_package' => ['type' => 'STRING', 'nullable' => true],
                            'summary' => ['type' => 'STRING', 'nullable' => true],
                        ],
                    ],
                ],
                'required' => [
                    'reply_markdown',
                    'intent',
                    'lead_level',
                    'recommended_product_ids',
                ],
            ],
        ];
    }

    /**
     * Safely parse raw JSON structured output or gracefully wrap plain Markdown prose as fallback.
     *
     * @param  array<string, mixed>  $context
     */
    public function parse(string $rawOutput, ?string $userMessage = null, array $context = []): ShowroomStructuredReply
    {
        $trimmed = trim($rawOutput);
        $decoded = $this->decodeJsonPayload($trimmed);

        if (is_array($decoded) && is_string($decoded['reply_markdown'] ?? null) && trim($decoded['reply_markdown']) !== '') {
            $replyMarkdown = trim((string) $decoded['reply_markdown']);
            $intent = $this->normalizeIntent($decoded['intent'] ?? null, $userMessage, $replyMarkdown, $context);
            $leadLevel = $this->normalizeLeadLevel($decoded['lead_level'] ?? null, $intent, $userMessage);
            $recommendedProductIds = $this->filterVerifiedActiveProductIds($decoded['recommended_product_ids'] ?? []);
            $servicePackage = $this->normalizeServicePackage($decoded['recommended_service_package'] ?? null, $intent, $userMessage, $replyMarkdown);
            $prefillBooking = $this->normalizePrefillBooking(
                rawPrefill: $decoded['prefill_booking'] ?? null,
                intent: $intent,
                recommendedProductIds: $recommendedProductIds,
                servicePackage: $servicePackage,
                context: $context,
            );

            return new ShowroomStructuredReply(
                replyMarkdown: $replyMarkdown,
                intent: $intent,
                leadLevel: $leadLevel,
                recommendedProductIds: $recommendedProductIds,
                recommendedServicePackage: $servicePackage,
                prefillBooking: $prefillBooking,
                usedFallback: false,
            );
        }

        // Fallback an toàn tuyệt đối cho plain-text Markdown (ví dụ SSE stream hoặc fake text trong test cũ)
        $intent = $this->normalizeIntent(null, $userMessage, $trimmed, $context);
        $leadLevel = $this->normalizeLeadLevel(null, $intent, $userMessage);
        $recommendedProductIds = $this->inferProductIdsFromContext($trimmed, $context);
        $servicePackage = $this->normalizeServicePackage(null, $intent, $userMessage, $trimmed);
        $prefillBooking = $this->normalizePrefillBooking(
            rawPrefill: null,
            intent: $intent,
            recommendedProductIds: $recommendedProductIds,
            servicePackage: $servicePackage,
            context: $context,
        );

        return new ShowroomStructuredReply(
            replyMarkdown: $trimmed,
            intent: $intent,
            leadLevel: $leadLevel,
            recommendedProductIds: $recommendedProductIds,
            recommendedServicePackage: $servicePackage,
            prefillBooking: $prefillBooking,
            usedFallback: true,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $candidate = $raw;
        if (preg_match('/^```(?:json)?\s*\R(.*?)\R```$/is', $raw, $matches) === 1) {
            $candidate = trim($matches[1]);
        }

        if (! str_starts_with($candidate, '{')) {
            return null;
        }

        $decoded = json_decode($candidate, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function normalizeIntent(mixed $rawIntent, ?string $userMessage, string $answer, array $context): string
    {
        if (is_string($rawIntent) && in_array($rawIntent, self::VALID_INTENTS, true)) {
            return $rawIntent;
        }

        $combined = Str::lower(trim((string) $userMessage).' '.$answer);
        $ascii = Str::ascii($combined);

        if (Str::contains($combined, ['đặt cọc', 'mua ngay', 'chốt', 'lăn bánh gấp'])
            || Str::contains($ascii, ['dat coc', 'mua ngay', 'chot xe', 'lan banh'])) {
            return 'hot_lead';
        }

        if (Str::contains($combined, ['bảo dưỡng', 'ceramic', 'rửa xe', 'ista', 'chăm sóc xe', 'phủ gốm'])
            || Str::contains($ascii, ['bao duong', 'ceramic', 'rua xe', 'ista', 'cham soc xe'])) {
            return 'service_booking';
        }

        if (Str::contains($combined, ['phụ kiện', 'thảm lót', 'camera hành trình', 'm performance'])
            || Str::contains($ascii, ['phu kien', 'tham lot', 'camera hanh trinh'])
            || (($context['current_product']['is_accessory'] ?? false) === true)) {
            return 'accessory_inquiry';
        }

        if (Str::contains($combined, ['lái thử', 'trải nghiệm'])
            || Str::contains($ascii, ['lai thu', 'trai nghiem'])) {
            return 'test_drive';
        }

        if (Str::contains($combined, ['tư vấn', 'báo giá', 'trả góp', 'so sánh'])
            || Str::contains($ascii, ['tu van', 'bao gia', 'tra gop', 'so sanh'])) {
            return 'consult';
        }

        return 'general_info';
    }

    private function normalizeLeadLevel(mixed $rawLevel, string $intent, ?string $userMessage): string
    {
        if (is_string($rawLevel) && in_array($rawLevel, self::VALID_LEAD_LEVELS, true)) {
            return $rawLevel;
        }

        if ($intent === 'hot_lead') {
            return 'hot';
        }

        $lowered = Str::lower((string) $userMessage);
        if (Str::contains($lowered, ['đặt cọc', 'mua ngay', 'tuần này', 'hôm nay', 'giá lăn bánh'])) {
            return 'hot';
        }

        return in_array($intent, ['test_drive', 'consult', 'service_booking', 'accessory_inquiry'], true)
            ? 'warm'
            : 'cold';
    }

    /**
     * Filter recommended product IDs strictly against active products in DB to eliminate hallucinated IDs.
     *
     * @return list<int>
     */
    private function filterVerifiedActiveProductIds(mixed $rawIds): array
    {
        if (! is_array($rawIds) || $rawIds === []) {
            return [];
        }

        $candidateIds = collect($rawIds)
            ->filter(static fn (mixed $id): bool => is_numeric($id) && (int) $id > 0)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->take(5)
            ->values();

        if ($candidateIds->isEmpty()) {
            return [];
        }

        $validIds = Product::query()
            ->active()
            ->whereIn('id', $candidateIds->all())
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $validLookup = array_flip($validIds);

        return $candidateIds
            ->filter(static fn (int $id): bool => isset($validLookup[$id]))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<int>
     */
    private function inferProductIdsFromContext(string $answer, array $context): array
    {
        $ids = [];

        if (isset($context['current_product']['id']) && is_numeric($context['current_product']['id'])) {
            $ids[] = (int) $context['current_product']['id'];
        }

        $loweredAnswer = Str::lower($answer);
        foreach ($context['products'] ?? [] as $product) {
            if (! is_array($product) || empty($product['id']) || empty($product['name'])) {
                continue;
            }
            if (Str::contains($loweredAnswer, Str::lower((string) $product['name']))) {
                $ids[] = (int) $product['id'];
            }
        }

        $ids = array_values(array_unique($ids));

        return $this->filterVerifiedActiveProductIds($ids);
    }

    private function normalizeServicePackage(
        mixed $rawPackage,
        string $intent,
        ?string $userMessage,
        string $answer,
    ): ?string {
        $officialPackages = array_keys(Appointment::servicePackageOptions());

        if (is_string($rawPackage) && trim($rawPackage) !== '') {
            $trimmed = trim($rawPackage);
            if (in_array($trimmed, $officialPackages, true)) {
                return $trimmed;
            }

            $lowerRaw = Str::ascii(Str::lower($trimmed));
            foreach ($officialPackages as $package) {
                $lowerPkg = Str::ascii(Str::lower($package));
                if (Str::contains($lowerPkg, $lowerRaw) || Str::contains($lowerRaw, $lowerPkg)) {
                    return $package;
                }
            }
        }

        if ($intent !== 'service_booking') {
            return null;
        }

        $combined = Str::ascii(Str::lower(trim((string) $userMessage).' '.$answer));

        if (Str::contains($combined, ['ceramic', 'hieu chinh son', 'paint protection'])) {
            return $officialPackages[1] ?? null;
        }
        if (Str::contains($combined, ['merino', 'khoang may', 'noi that da'])) {
            return $officialPackages[2] ?? null;
        }
        if (Str::contains($combined, ['ista', 'chan doan dien tu', 'tong quat'])) {
            return $officialPackages[3] ?? null;
        }
        if (Str::contains($combined, ['bao duong', 'cbs', 'maintenance'])) {
            return $officialPackages[0] ?? null;
        }

        return null;
    }

    /**
     * @param  list<int>  $recommendedProductIds
     * @param  array<string, mixed>  $context
     * @return array{type: string, product_id: ?int, service_package: ?string, summary: ?string, url: string}|null
     */
    private function normalizePrefillBooking(
        mixed $rawPrefill,
        string $intent,
        array $recommendedProductIds,
        ?string $servicePackage,
        array $context,
    ): ?array {
        $bookingType = null;
        $productId = null;
        $prefillPackage = $servicePackage;
        $summary = null;

        if (is_array($rawPrefill)) {
            $rawType = trim((string) ($rawPrefill['type'] ?? ''));
            if (in_array($rawType, self::VALID_BOOKING_TYPES, true)) {
                $bookingType = $rawType;
            } elseif ($rawType === 'service') {
                $bookingType = 'maintenance';
            }

            if (! empty($rawPrefill['product_id']) && is_numeric($rawPrefill['product_id'])) {
                $verified = $this->filterVerifiedActiveProductIds([(int) $rawPrefill['product_id']]);
                $productId = $verified[0] ?? null;
            }

            if (is_string($rawPrefill['service_package'] ?? null)) {
                $matchedPackage = $this->normalizeServicePackage($rawPrefill['service_package'], 'service_booking', null, '');
                if ($matchedPackage !== null) {
                    $prefillPackage = $matchedPackage;
                }
            }

            if (is_string($rawPrefill['summary'] ?? null) && trim($rawPrefill['summary']) !== '') {
                $summary = Str::limit(trim($rawPrefill['summary']), 180);
            }
        }

        if ($bookingType === null) {
            if ($intent === 'service_booking' || $prefillPackage !== null) {
                $bookingType = 'maintenance';
            } elseif ($intent === 'test_drive') {
                $bookingType = 'test_drive';
            } elseif (in_array($intent, ['hot_lead', 'consult'], true)) {
                $bookingType = 'consult';
            }
        }

        if ($bookingType === null) {
            return null;
        }

        if ($productId === null && $bookingType !== 'maintenance') {
            $productId = $recommendedProductIds[0]
                ?? (isset($context['current_product']['id']) && ! ($context['current_product']['is_accessory'] ?? false)
                    ? (int) $context['current_product']['id']
                    : null);

            if ($productId !== null) {
                $productId = $this->filterVerifiedActiveProductIds([$productId])[0] ?? null;
            }
        }

        $query = ['type' => $bookingType === 'maintenance' ? 'service' : $bookingType];
        if ($productId !== null) {
            $query['product_id'] = $productId;
        }
        if ($prefillPackage !== null) {
            $query['service_package'] = $prefillPackage;
        }

        return [
            'type' => $bookingType,
            'product_id' => $productId,
            'service_package' => $prefillPackage,
            'summary' => $summary,
            'url' => route('appointments.create', $query, false),
        ];
    }
}
