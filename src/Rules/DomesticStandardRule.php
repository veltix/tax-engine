<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class DomesticStandardRule implements RuleContract
{
    public function __construct(
        private RateRepositoryContract $rates,
    ) {}

    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        return $transaction->isDomestic()
            && $transaction->sellerCountry->isEu();
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
            ruleApplied: 'domestic_standard',
            reasoning: "Domestic sale within {$transaction->sellerCountry->code} — standard VAT applies",
        );
    }

    public function priority(): int
    {
        return 10;
    }
}
