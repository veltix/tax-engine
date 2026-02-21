<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Exceptions;

use RuntimeException;

final class NoApplicableRuleException extends RuntimeException
{
    public static function forTransaction(string $transactionId): self
    {
        return new self("No applicable tax rule found for transaction: {$transactionId}");
    }
}
