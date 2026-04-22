<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Hủy orders hết hạn và hoàn lại stock sản phẩm';

    public function handle(): int
    {
        $expiredOrders = Order::pendingExpired()->with('items.product')->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Không có order nào hết hạn.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order, &$count) {
                /** @var Order $order */
                // Hoàn stock cho từng item — sorted by product_id để tránh deadlock
                $sortedItems = $order->items->sortBy('product_id');

                foreach ($sortedItems as $item) {
                    $item->product()->lockForUpdate()->first()
                        ?->increment('stock', $item->quantity);
                }

                $order->update(['status' => OrderStatus::Expired]);
                $count++;
            });
        }

        $this->info("Đã hủy {$count} order hết hạn và hoàn lại stock.");

        return self::SUCCESS;
    }
}
