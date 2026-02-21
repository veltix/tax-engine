<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

final class EuVatRates
{
    public const array REDUCED_RATES = [
        'AT' => ['reduced' => '10.00', 'reduced_second' => '13.00', 'parking' => '13.00'],
        'BE' => ['reduced' => '6.00', 'reduced_second' => '12.00', 'parking' => '12.00'],
        'BG' => ['reduced' => '9.00'],
        'HR' => ['reduced' => '5.00', 'reduced_second' => '13.00'],
        'CY' => ['reduced' => '5.00', 'reduced_second' => '9.00'],
        'CZ' => ['reduced' => '12.00'],
        'DK' => [],
        'EE' => ['reduced' => '9.00'],
        'FI' => ['reduced' => '10.00', 'reduced_second' => '14.00'],
        'FR' => ['reduced' => '5.50', 'reduced_second' => '10.00', 'super_reduced' => '2.10'],
        'DE' => ['reduced' => '7.00'],
        'GR' => ['reduced' => '6.00', 'reduced_second' => '13.00'],
        'HU' => ['reduced' => '5.00', 'reduced_second' => '18.00'],
        'IE' => ['reduced' => '9.00', 'reduced_second' => '13.50', 'super_reduced' => '4.80', 'parking' => '13.50'],
        'IT' => ['reduced' => '5.00', 'reduced_second' => '10.00', 'super_reduced' => '4.00'],
        'LV' => ['reduced' => '5.00', 'reduced_second' => '12.00'],
        'LT' => ['reduced' => '5.00', 'reduced_second' => '9.00'],
        'LU' => ['reduced' => '8.00', 'super_reduced' => '3.00', 'parking' => '14.00'],
        'MT' => ['reduced' => '5.00', 'reduced_second' => '7.00'],
        'NL' => ['reduced' => '9.00'],
        'PL' => ['reduced' => '5.00', 'reduced_second' => '8.00'],
        'PT' => ['reduced' => '6.00', 'reduced_second' => '13.00', 'parking' => '13.00'],
        'RO' => ['reduced' => '5.00', 'reduced_second' => '9.00'],
        'SK' => ['reduced' => '10.00'],
        'SI' => ['reduced' => '5.00', 'reduced_second' => '9.50'],
        'ES' => ['reduced' => '10.00', 'super_reduced' => '4.00'],
        'SE' => ['reduced' => '6.00', 'reduced_second' => '12.00'],
    ];

    public static function version(): string
    {
        return '2025.1';
    }

    public static function standardRate(string $countryCode): ?string
    {
        return Country::STANDARD_RATES[$countryCode] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function reducedRates(string $countryCode): array
    {
        return self::REDUCED_RATES[$countryCode] ?? [];
    }

    public static function rateForSupplyType(string $countryCode, string $supplyType, ?string $category = null): string
    {
        if ($category !== null) {
            $reduced = self::reducedRates($countryCode);

            if (isset($reduced[$category])) {
                return $reduced[$category];
            }
        }

        return self::standardRate($countryCode) ?? '';
    }
}
