<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Money;

final readonly class InvoiceLineData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $lineId,
        public Money $amount,
        public SupplyType $supplyType,
        public ?string $description = null,
        public array $metadata = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            lineId: $data['lineId'],
            amount: $data['amount'] instanceof Money
                ? $data['amount']
                : Money::fromCents($data['amount']),
            supplyType: $data['supplyType'] instanceof SupplyType
                ? $data['supplyType']
                : SupplyType::from($data['supplyType']),
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
