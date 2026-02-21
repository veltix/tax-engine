<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Policies;

use Veltix\TaxEngine\Contracts\ValidationPolicyContract;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\VatOutagePolicy;

final class DefaultValidationPolicy implements ValidationPolicyContract
{
    public function shouldValidateVat(TransactionData $transaction): bool
    {
        return $transaction->isB2B()
            && $transaction->buyerVatNumber !== null
            && $transaction->buyerVatNumber !== '';
    }

    public function onViesOutage(TransactionData $transaction): VatOutagePolicy
    {
        return VatOutagePolicy::Allow;
    }
}
