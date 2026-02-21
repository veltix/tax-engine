<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class DomesticReverseChargeRule implements RuleContract
{
    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        return $transaction->isDomestic()
            && $transaction->sellerCountry->isEu()
            && $transaction->isB2B()
            && ($transaction->metadata['domestic_reverse_charge'] ?? false) === true;
    }

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData
    {
        return new TaxDecisionData(
            scheme: TaxScheme::DomesticReverseCharge,
            rate: '0.00',
            taxCountry: $transaction->sellerCountry,
            ruleApplied: 'domestic_reverse_charge',
            reasoning: "Domestic reverse charge in {$transaction->sellerCountry->code} — buyer accounts for VAT",
            reverseCharged: true,
        );
    }

    public function priority(): int
    {
        return 30;
    }
}
