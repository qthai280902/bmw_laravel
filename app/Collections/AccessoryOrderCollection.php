<?php

namespace App\Collections;

use App\Models\AccessoryOrder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, AccessoryOrder>
 */
class AccessoryOrderCollection extends Collection
{
    /**
     * @return array{total: int, pending: int, confirmed: int, completed: int, cancelled: int}
     */
    public function statusCounts(): array
    {
        $counts = [
            'total' => $this->count(),
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($this as $order) {
            $status = (string) $order->status;
            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function completedRevenue(): int
    {
        return (int) $this
            ->where('status', AccessoryOrder::STATUS_COMPLETED)
            ->sum(static fn (AccessoryOrder $order): int => (int) $order->total_amount);
    }

    public function processingValue(): int
    {
        return (int) $this
            ->filter(static fn (AccessoryOrder $order): bool => in_array($order->status, [
                AccessoryOrder::STATUS_PENDING,
                AccessoryOrder::STATUS_CONFIRMED,
            ], true))
            ->sum(static fn (AccessoryOrder $order): int => (int) $order->total_amount);
    }

    public function cancelledValue(): int
    {
        return (int) $this
            ->where('status', AccessoryOrder::STATUS_CANCELLED)
            ->sum(static fn (AccessoryOrder $order): int => (int) $order->total_amount);
    }

    public function completionRate(): float
    {
        $counts = $this->statusCounts();

        return $counts['total'] > 0
            ? round(($counts['completed'] / $counts['total']) * 100, 1)
            : 0.0;
    }
}
