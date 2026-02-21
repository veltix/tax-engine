<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class CrossBorderB2CFallbackRule implements RuleContract
{
    public function __construct(
        private RateRepositoryContract $rates,
    ) {}

    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        return $transaction->isCrossBorderEu()
            && $transaction->isB2C();
    }

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData
    {
        $rate = $this->rates->rateForSupplyType(
            $transaction->sellerCountry,
            $transaction->supplyType,
            $transaction->metadata['rate_category'] ?? null,
        );

        return new TaxDecisionData(
            scheme: TaxScheme::Standard,
            rate: $rate,
            taxCountry: $transaction->sellerCountry,
            ruleApplied: 'cross_border_b2c_fallback',
            reasoning: "Cross-border B2C EU sale without OSS — seller country {$transaction->sellerCountry->code} VAT applies (sub-threshold fallback)",
        );
    }

    public function priority(): int
    {
        return 20;
    }
}
