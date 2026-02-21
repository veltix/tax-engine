<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Exceptions;

use RuntimeException;

final class VatValidationException extends RuntimeException
{
    public static function serviceUnavailable(string $message = 'VIES service is currently unavailable'): self
    {
        return new self($message, 503);
    }

    public static function serviceError(string $message = 'VIES service returned an error'): self
    {
        return new self($message, 500);
    }
}
