<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Enums\PriceMode;
use Veltix\TaxEngine\Enums\RoundingStrategy;

final readonly class TaxCalculationContext
{
    public function __construct(
        public ?VatValidationResultData $vatResult = null,
        public PriceMode $priceMode = PriceMode::TaxExclusive,
        public RoundingStrategy $roundingStrategy = RoundingStrategy::PerLine,
        public ?LegalEntityData $legalEntity = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'vatResult' => $this->vatResult?->toArray(),
            'priceMode' => $this->priceMode->value,
            'roundingStrategy' => $this->roundingStrategy->value,
            'legalEntity' => $this->legalEntity?->toArray(),
        ];
    }
}
