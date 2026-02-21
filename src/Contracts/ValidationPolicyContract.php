<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\VatOutagePolicy;

interface ValidationPolicyContract
{
    public function shouldValidateVat(TransactionData $transaction): bool;

    public function onViesOutage(TransactionData $transaction): VatOutagePolicy;
}
