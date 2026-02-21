<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Money;

final readonly class TaxResultData
{
    public function __construct(
        public Money $netAmount,
        public Money $taxAmount,
        public Money $grossAmount,
        public TaxDecisionData $decision,
        public string $transactionId,
    ) {}

    public function effectiveRate(): string
    {
        if ($this->netAmount->isZero()) {
            return '0.00';
        }

        $taxDecimal = bcdiv((string) $this->taxAmount->amount, (string) $this->netAmount->amount, 10);

        return bcmul($taxDecimal, '100', 2);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'netAmount' => $this->netAmount->toArray(),
            'taxAmount' => $this->taxAmount->toArray(),
            'grossAmount' => $this->grossAmount->toArray(),
            'decision' => $this->decision->toArray(),
            'transactionId' => $this->transactionId,
            'effectiveRate' => $this->effectiveRate(),
        ];
    }
}
