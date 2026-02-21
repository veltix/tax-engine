<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Exceptions;

use RuntimeException;

final class InsufficientEvidenceException extends RuntimeException
{
    public static function minimumNotMet(int $required, int $provided): self
    {
        return new self("Insufficient evidence: {$provided} signal(s) provided, minimum {$required} required");
    }
}
