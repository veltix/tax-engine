<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Repositories;

use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Exceptions\RateNotFoundException;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\EuVatRates;

final class StaticRateRepository implements RateRepositoryContract
{
    public function standardRate(Country $country): string
    {
        $rate = EuVatRates::standardRate($country->code);

        if ($rate === null) {
            throw RateNotFoundException::forCountry($country->code);
        }

        return $rate;
    }

    /**
     * @return array<string, string>
     */
    public function reducedRates(Country $country): array
    {
        return EuVatRates::reducedRates($country->code);
    }

    public function rateForSupplyType(Country $country, SupplyType $supplyType, ?string $category = null): string
    {
        $rate = EuVatRates::rateForSupplyType($country->code, $supplyType->value, $category);

        if ($rate === '') {
            throw RateNotFoundException::forSupplyType($country->code, $supplyType->value);
        }

        return $rate;
    }
}
