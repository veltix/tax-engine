<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Policies;

use Veltix\TaxEngine\Contracts\PlaceOfSupplyPolicyContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Support\Country;

final class DefaultPlaceOfSupplyPolicy implements PlaceOfSupplyPolicyContract
{
    public function resolve(TransactionData $transaction, TaxCalculationContext $context): Country
    {
        if ($transaction->isDomestic()) {
            return $transaction->sellerCountry;
        }

        if ($transaction->isB2B() && $transaction->buyerVatNumber !== null) {
            return $transaction->buyerCountry;
        }

        return $transaction->buyerCountry;
    }
}
