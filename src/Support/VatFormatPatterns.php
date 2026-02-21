<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

final class VatFormatPatterns
{
    public const array PATTERNS = [
        'AT' => '/^U\d{8}$/',
        'BE' => '/^[01]\d{9}$/',
        'BG' => '/^\d{9,10}$/',
        'HR' => '/^\d{11}$/',
        'CY' => '/^\d{8}[A-Z]$/',
        'CZ' => '/^\d{8,10}$/',
        'DK' => '/^\d{8}$/',
        'EE' => '/^\d{9}$/',
        'FI' => '/^\d{8}$/',
        'FR' => '/^[A-HJ-NP-Z0-9]{2}\d{9}$/',
        'DE' => '/^\d{9}$/',
        'GR' => '/^\d{9}$/',
        'HU' => '/^\d{8}$/',
        'IE' => '/^\d{7}[A-Z]{1,2}$|^\d[A-Z+*]\d{5}[A-Z]$/',
        'IT' => '/^\d{11}$/',
        'LV' => '/^\d{11}$/',
        'LT' => '/^(\d{9}|\d{12})$/',
        'LU' => '/^\d{8}$/',
        'MT' => '/^\d{8}$/',
        'NL' => '/^\d{9}B\d{2}$/',
        'PL' => '/^\d{10}$/',
        'PT' => '/^\d{9}$/',
        'RO' => '/^\d{2,10}$/',
        'SK' => '/^\d{10}$/',
        'SI' => '/^\d{8}$/',
        'ES' => '/^[A-Z]\d{7}[A-Z]$|^[A-Z]\d{8}$|^\d{8}[A-Z]$/',
        'SE' => '/^\d{12}$/',
    ];

    public const array VIES_PREFIX_MAP = [
        'GR' => 'EL',
    ];

    public static function hasPattern(string $countryCode): bool
    {
        return isset(self::PATTERNS[strtoupper($countryCode)]);
    }

    public static function getPattern(string $countryCode): ?string
    {
        return self::PATTERNS[strtoupper($countryCode)] ?? null;
    }

    public static function viesPrefix(string $countryCode): string
    {
        $code = strtoupper($countryCode);

        return self::VIES_PREFIX_MAP[$code] ?? $code;
    }
}
