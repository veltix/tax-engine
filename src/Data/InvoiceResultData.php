<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Money;

final readonly class InvoiceResultData
{
    /**
     * @param InvoiceLineResultData[]    $lineResults
     * @param array<string, Money>       $taxSummary   Keyed by rate string, e.g. ['21.00' => Money(305)]
     */
    public function __construct(
        public string $invoiceId,
        public array $lineResults,
        public Money $totalNet,
        public Money $totalTax,
        public Money $totalGross,
        public array $taxSummary,
    ) {}
}
