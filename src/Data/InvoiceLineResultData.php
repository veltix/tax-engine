<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Money;

final readonly class InvoiceLineResultData
{
    public function __construct(
        public string $lineId,
        public Money $netAmount,
        public Money $allocatedTax,
        public Money $grossAmount,
        public TaxDecisionData $decision,
    ) {}
}
