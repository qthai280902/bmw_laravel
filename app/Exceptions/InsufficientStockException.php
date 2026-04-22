<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requestedQuantity = 1
    ) {
        parent::__construct(
            "Sản phẩm \"{$product->name}\" không đủ hàng. Yêu cầu: {$requestedQuantity}, Còn lại: {$product->stock}."
        );
    }
}
