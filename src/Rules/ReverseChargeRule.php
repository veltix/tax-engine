<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class ReverseChargeRule implements RuleContract
{
    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        if (! $transaction->isCrossBorderEu() || ! $transaction->isB2B()) {
            return false;
        }

        if ($transaction->buyerVatNumber === null || $transaction->buyerVatNumber === '') {
            return false;
        }

        if ($context->vatResult === null) {
            return false;
        }

        if ($context->vatResult->valid) {
            return true;
        }

        // Fail-open for VIES outage: format is valid but service was unavailable
        if ($context->vatResult->formatValid && $context->vatResult->failureReason !== null
            && str_contains($context->vatResult->failureReason, 'unavailable')) {
            return true;
        }

        return false;
    }

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData
    {
        return new TaxDecisionData(
            scheme: TaxScheme::ReverseCharge,
            rate: '0.00',
            taxCountry: $transaction->buyerCountry,
            ruleApplied: 'reverse_charge',
            reasoning: "B2B cross-border EU sale with validated VAT number — reverse charge applies",
            vatNumberValidated: $context->vatResult !== null && $context->vatResult->valid,
            reverseCharged: true,
        );
    }

    public function priority(): int
    {
        return 50;
    }
}
