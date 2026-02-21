<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;

interface RuleContract
{
    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool;

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData;

    public function priority(): int;
}
