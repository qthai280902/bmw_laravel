<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class InsufficientAccessoryStockException extends RuntimeException
{
    public function __construct(
        string $message = 'Rất tiếc, phụ kiện này vừa hết hàng hoặc không đủ số lượng trong kho.',
        int $code = 409,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
