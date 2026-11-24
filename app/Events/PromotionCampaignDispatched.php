<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PromotionCampaignDispatched implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<array{email: string, name: string, vehicle_name: string, advisor_name?: string, phone?: string}>  $recipients
     */
    public function __construct(
        public string $campaignId,
        public string $offerTitle,
        public string $offerContent,
        public array $recipients,
        public ?int $sentBy = null,
    ) {}
}
