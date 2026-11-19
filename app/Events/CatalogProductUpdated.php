<?php

namespace App\Events;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CatalogProductUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public string $description,
        public ?User $actor = null,
    ) {}
}
