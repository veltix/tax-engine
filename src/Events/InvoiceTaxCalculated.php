<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Events;

use Veltix\TaxEngine\Data\InvoiceData;
use Veltix\TaxEngine\Data\InvoiceResultData;

final class InvoiceTaxCalculated
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly InvoiceData $invoice,
        public readonly InvoiceResultData $result,
        public readonly array $metadata = [],
    ) {}
}
