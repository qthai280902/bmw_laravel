<?php

namespace App\Services\Ai\Dto;

final readonly class ShowroomStructuredReply
{
    /**
     * @param  list<int>  $recommendedProductIds
     * @param  array{type: string, product_id: ?int, service_package: ?string, summary: ?string, url: string}|null  $prefillBooking
     */
    public function __construct(
        public string $replyMarkdown,
        public string $intent = 'general_info',
        public string $leadLevel = 'cold',
        public array $recommendedProductIds = [],
        public ?string $recommendedServicePackage = null,
        public ?array $prefillBooking = null,
        public bool $usedFallback = false,
    ) {}

    /**
     * @return array{
     *     reply_markdown: string,
     *     intent: string,
     *     lead_level: string,
     *     recommended_product_ids: list<int>,
     *     recommended_service_package: ?string,
     *     prefill_booking: array{type: string, product_id: ?int, service_package: ?string, summary: ?string, url: string}|null,
     *     used_fallback: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'reply_markdown' => $this->replyMarkdown,
            'intent' => $this->intent,
            'lead_level' => $this->leadLevel,
            'recommended_product_ids' => $this->recommendedProductIds,
            'recommended_service_package' => $this->recommendedServicePackage,
            'prefill_booking' => $this->prefillBooking,
            'used_fallback' => $this->usedFallback,
        ];
    }
}
