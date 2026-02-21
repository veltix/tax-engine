<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

use InvalidArgumentException;

final readonly class Country
{
    public const array EU_MEMBERS = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    public const array STANDARD_RATES = [
        'AT' => '20.00',
        'BE' => '21.00',
        'BG' => '20.00',
        'HR' => '25.00',
        'CY' => '19.00',
        'CZ' => '21.00',
        'DK' => '25.00',
        'EE' => '24.00',
        'FI' => '25.50',
        'FR' => '20.00',
        'DE' => '19.00',
        'GR' => '24.00',
        'HU' => '27.00',
        'IE' => '23.00',
        'IT' => '22.00',
        'LV' => '21.00',
        'LT' => '21.00',
        'LU' => '17.00',
        'MT' => '18.00',
        'NL' => '21.00',
        'PL' => '23.00',
        'PT' => '23.00',
        'RO' => '19.00',
        'SK' => '23.00',
        'SI' => '22.00',
        'ES' => '21.00',
        'SE' => '25.00',
    ];

    public string $code;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            throw new InvalidArgumentException(
                "Invalid ISO 3166-1 alpha-2 country code: {$code}"
            );
        }

        $this->code = $code;
    }

    public function isEu(): bool
    {
        return in_array($this->code, self::EU_MEMBERS, true);
    }

    public function standardVatRate(): ?string
    {
        return self::STANDARD_RATES[$this->code] ?? null;
    }

    public function isSameAs(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function isDomesticTo(self $other): bool
    {
        return $this->isSameAs($other);
    }

    public function isCrossBorderEu(self $other): bool
    {
        return $this->isEu()
            && $other->isEu()
            && ! $this->isSameAs($other);
    }

    public function isOutsideEu(): bool
    {
        return ! $this->isEu();
    }

    /**
     * @return self[]
     */
    public static function euMembers(): array
    {
        return array_map(
            fn (string $code) => new self($code),
            self::EU_MEMBERS
        );
    }
}
