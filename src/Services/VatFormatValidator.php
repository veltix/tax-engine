<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Support\VatFormatPatterns;

final class VatFormatValidator
{
    private const array REVERSE_VIES_PREFIX_MAP = [
        'EL' => 'GR',
    ];

    /**
     * Parse a VAT number into [countryCode, vatNumber].
     *
     * Accepts formats like "DE123456789", "EL123456789", or just "123456789" with explicit country code.
     *
     * @return array{0: string, 1: string}
     */
    public static function parse(string $vatNumber, ?string $countryCode = null): array
    {
        $vatNumber = preg_replace('/[\s.\-]/', '', $vatNumber) ?? $vatNumber;

        if ($countryCode === null && preg_match('/^([A-Z]{2})(.+)$/i', $vatNumber, $matches)) {
            $prefix = strtoupper($matches[1]);
            $number = $matches[2];

            // Handle VIES prefix (EL → GR)
            $countryCode = self::REVERSE_VIES_PREFIX_MAP[$prefix] ?? $prefix;

            return [$countryCode, $number];
        }

        $countryCode = strtoupper($countryCode ?? '');

        return [$countryCode, $vatNumber];
    }

    public static function isValidFormat(string $countryCode, string $vatNumber): bool
    {
        $countryCode = strtoupper($countryCode);

        if (! VatFormatPatterns::hasPattern($countryCode)) {
            return true; // Non-EU: can't reject
        }

        $pattern = VatFormatPatterns::getPattern($countryCode);

        if ($pattern === null) {
            return true;
        }

        return (bool) preg_match($pattern, $vatNumber);
    }
}
