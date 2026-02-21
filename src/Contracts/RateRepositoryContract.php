<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;

interface RateRepositoryContract
{
    public function standardRate(Country $country): string;

    /** @return array<string, string> */
    public function reducedRates(Country $country): array;

    public function rateForSupplyType(Country $country, SupplyType $supplyType, ?string $category = null): string;
}
