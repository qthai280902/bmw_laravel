<?php

namespace App\Exceptions;

use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public static function forTransition(string $currentLabel, string $nextLabel, ?string $guidance = null): self
    {
        $message = "Không thể chuyển trạng thái từ '{$currentLabel}' sang '{$nextLabel}'.";

        if ($guidance !== null && $guidance !== '') {
            $message .= ' '.$guidance;
        }

        return new self($message);
    }

    public static function forTerminalStatus(string $currentLabel): self
    {
        return new self("Hồ sơ đã ở trạng thái '{$currentLabel}' (đã đóng) nên không thể thay đổi trạng thái.");
    }
}
