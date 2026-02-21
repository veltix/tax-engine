<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Exceptions;

use RuntimeException;

final class EvidenceConflictException extends RuntimeException
{
    /** @param string[] $countries */
    public static function conflictDetected(array $countries): self
    {
        $list = implode(', ', $countries);

        return new self("Evidence conflict detected between countries: {$list}");
    }
}
