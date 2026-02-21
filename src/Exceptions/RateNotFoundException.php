<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Exceptions;

use RuntimeException;

final class RateNotFoundException extends RuntimeException
{
    public static function forCountry(string $countryCode): self
    {
        return new self("No VAT rate found for country: {$countryCode}");
    }

    public static function forSupplyType(string $countryCode, string $supplyType): self
    {
        return new self("No VAT rate found for supply type '{$supplyType}' in country: {$countryCode}");
    }
}
