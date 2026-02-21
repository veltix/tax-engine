<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Support\Country;

interface PlaceOfSupplyPolicyContract
{
    public function resolve(TransactionData $transaction, TaxCalculationContext $context): Country;
}
