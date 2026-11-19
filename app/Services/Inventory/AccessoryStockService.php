<?php

namespace App\Services\Inventory;

use App\Exceptions\InsufficientAccessoryStockException;
use App\Models\AccessoryOrder;
use App\Models\Product;
use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccessoryStockService
{
    public const LOCK_TTL_SECONDS = 10;

    public const LOCK_BLOCK_SECONDS = 5;

    public function __construct(
        private readonly ShowroomCacheService $cacheService,
    ) {}

    /**
     * Place an accessory order atomically under Cache::lock + DB::transaction + lockForUpdate().
     *
     * @param  array<string, mixed>  $orderAttributes
     */
    public function placeOrder(Product $product, array $orderAttributes, string $channel = 'Web'): AccessoryOrder
    {
        $lockKey = $this->lockKey((int) $product->id);

        /** @var AccessoryOrder $order */
        $order = Cache::lock($lockKey, self::LOCK_TTL_SECONDS)
            ->block(self::LOCK_BLOCK_SECONDS, function () use ($product, $orderAttributes, $channel): AccessoryOrder {
                return DB::transaction(function () use ($product, $orderAttributes, $channel): AccessoryOrder {
                    $lockedProduct = Product::query()
                        ->whereKey($product->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    abort_unless((bool) $lockedProduct->is_active && $lockedProduct->canOrderAccessory(), 404);

                    $quantity = max(1, (int) ($orderAttributes['quantity'] ?? 1));
                    $hasManagedStock = $lockedProduct->stock !== null;

                    if ($hasManagedStock && (int) $lockedProduct->stock < $quantity) {
                        throw new InsufficientAccessoryStockException(
                            'Rất tiếc, phụ kiện này vừa hết hàng hoặc không đủ số lượng trong kho.'
                        );
                    }

                    if ($hasManagedStock) {
                        $lockedProduct->stock = max(0, (int) $lockedProduct->stock - $quantity);
                        $lockedProduct->save();
                    }

                    $order = AccessoryOrder::create([
                        ...$orderAttributes,
                        'product_id' => $lockedProduct->id,
                    ]);

                    $meta = is_array($order->meta_data) ? $order->meta_data : [];
                    $meta['stock_deducted'] = $hasManagedStock;
                    $meta['stock_deducted_quantity'] = $quantity;
                    $meta['stock_deducted_at'] = now()->toIso8601String();

                    $meta = InteractionTimelineRecorder::appendToMeta(
                        meta: $meta,
                        actorType: InteractionTimelineRecorder::ACTOR_CUSTOMER,
                        actorLabel: 'Khách hàng · '.$order->customer_name,
                        title: "Đặt đơn phụ kiện #ACC-{$order->id}",
                        detail: "Đặt mua {$order->quantity} × {$lockedProduct->name} (tồn kho còn ".(int) ($lockedProduct->stock ?? 0).") — Giao tới {$order->customer_address}.",
                        channel: $channel,
                        occurredAt: $order->created_at ?? now(),
                    );

                    if (filled($order->customer_email)) {
                        $meta = InteractionTimelineRecorder::appendToMeta(
                            meta: $meta,
                            actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
                            actorLabel: 'Hệ thống tự động',
                            title: 'Gửi email tiếp nhận đơn phụ kiện',
                            detail: "Hệ thống gửi xác nhận tiếp nhận đơn #ACC-{$order->id} tới {$order->customer_email}.",
                            channel: 'Email',
                            occurredAt: $order->created_at ?? now(),
                        );
                    }

                    $order->forceFill(['meta_data' => $meta])->saveQuietly();
                    $order->setRelation('product', $lockedProduct);

                    return $order;
                });
            });

        $this->cacheService->flushCatalog();
        $this->cacheService->flushDashboard();

        return $order;
    }

    /**
     * Atomically restore accessory stock exactly once when an order transitions to cancelled.
     */
    public function restoreStockOnCancel(AccessoryOrder $order, ?User $actor = null): bool
    {
        if (! $order->product_id) {
            return false;
        }

        $lockKey = $this->lockKey((int) $order->product_id);

        $restored = Cache::lock($lockKey, self::LOCK_TTL_SECONDS)
            ->block(self::LOCK_BLOCK_SECONDS, function () use ($order, $actor): bool {
                return DB::transaction(function () use ($order, $actor): bool {
                    $lockedOrder = AccessoryOrder::query()
                        ->whereKey($order->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $lockedOrder) {
                        return false;
                    }

                    $meta = is_array($lockedOrder->meta_data) ? $lockedOrder->meta_data : [];

                    if (($meta['stock_deducted'] ?? false) !== true || ! empty($meta['stock_restored_at'])) {
                        return false;
                    }

                    $quantity = max(1, (int) ($meta['stock_deducted_quantity'] ?? $lockedOrder->quantity));

                    $lockedProduct = Product::query()
                        ->whereKey($lockedOrder->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lockedProduct && $lockedProduct->stock !== null) {
                        $lockedProduct->stock = (int) $lockedProduct->stock + $quantity;
                        $lockedProduct->save();
                    }

                    $meta['stock_restored_at'] = now()->toIso8601String();
                    $meta['stock_restored_quantity'] = $quantity;

                    $meta = InteractionTimelineRecorder::appendToMeta(
                        meta: $meta,
                        actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
                        actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
                        title: "Hoàn {$quantity} sản phẩm về kho",
                        detail: "Hoàn trả {$quantity} sản phẩm về tồn kho do đơn phụ kiện #ACC-{$lockedOrder->id} đã hủy.",
                        channel: 'CRM',
                    );

                    $lockedOrder->forceFill(['meta_data' => $meta])->saveQuietly();
                    $order->forceFill(['meta_data' => $meta]);

                    return true;
                });
            });

        if ($restored) {
            $this->cacheService->flushCatalog();
            $this->cacheService->flushDashboard();
        }

        return $restored;
    }

    public function lockKey(int $productId): string
    {
        return "accessory-stock:{$productId}";
    }
}
