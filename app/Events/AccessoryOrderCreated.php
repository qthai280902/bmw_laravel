<?php

namespace App\Events;

use App\Models\AccessoryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessoryOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public AccessoryOrder $order) {}
}
