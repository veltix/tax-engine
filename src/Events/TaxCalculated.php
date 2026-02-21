<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Events;

use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;

final class TaxCalculated
{
    public function __construct(
        public readonly TransactionData $transaction,
        public readonly TaxResultData $result,
        public readonly ?VatValidationResultData $vatResult = null,
        /** @var array<string, mixed> */
        public readonly array $metadata = [],
    ) {}
}
