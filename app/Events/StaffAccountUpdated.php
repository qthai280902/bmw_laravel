<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffAccountUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $targetUser,
        public string $action,
        public string $description,
        public ?User $actor = null,
    ) {}
}
