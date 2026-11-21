<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingDailyClosing extends Model
{
    protected $fillable = [
        'closing_date',
        'closed_by',
        'closed_at',
        'appointments_count',
        'services_count',
        'orders_count',
        'completed_revenue',
        'committed_value',
        'cancelled_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'closed_at' => 'datetime',
            'appointments_count' => 'integer',
            'services_count' => 'integer',
            'orders_count' => 'integer',
            'completed_revenue' => 'integer',
            'committed_value' => 'integer',
            'cancelled_value' => 'integer',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
