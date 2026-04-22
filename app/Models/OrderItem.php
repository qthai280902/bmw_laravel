<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'deposit_amount_snapshot',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'deposit_amount_snapshot' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Tổng tiền cọc của dòng này = snapshot price * quantity.
     */
    public function getSubtotalAttribute(): int
    {
        return $this->deposit_amount_snapshot * $this->quantity;
    }
}
