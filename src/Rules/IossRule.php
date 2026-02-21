<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Money;

final readonly class IossRule implements RuleContract
{
    /**
     * @param string[] $iossExcludedCategories
     */
    public function __construct(
        private RateRepositoryContract $rates,
        private bool $iossEnabled = false,
        private int $iossConsignmentMaxCents = 15000,
        private array $iossExcludedCategories = [],
    ) {}

    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        if (! $this->iossEnabled) {
            return false;
        }

        if ($transaction->supplyType !== SupplyType::Goods) {
            return false;
        }

        if (! $transaction->buyerCountry->isEu()) {
            return false;
        }

        if (! $transaction->sellerCountry->isOutsideEu()) {
            return false;
        }

        // Check legal entity IOSS registration if available
        if ($context->legalEntity !== null && ! $context->legalEntity->iossRegistered) {
            return false;
        }

        // Check excluded categories
        $category = $transaction->metadata['rate_category'] ?? null;
        if ($category !== null && in_array($category, $this->iossExcludedCategories, true)) {
            return false;
        }

        // Check consignment value from metadata or fall back to transaction amount
        $consignmentCents = $transaction->metadata['consignment_value_cents'] ?? null;
        if ($consignmentCents !== null) {
            if ((int) $consignmentCents > $this->iossConsignmentMaxCents) {
                return false;
            }
        } elseif ($transaction->amount->currency === 'EUR') {
            $threshold = Money::fromCents($this->iossConsignmentMaxCents, 'EUR');
            if ($transaction->amount->greaterThan($threshold)) {
                return false;
            }
        }

        return true;
    }

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData
    {
        $rate = $this->rates->rateForSupplyType(
            $transaction->buyerCountry,
            $transaction->supplyType,
            $transaction->metadata['rate_category'] ?? null,
        );

        return new TaxDecisionData(
            scheme: TaxScheme::IOSS,
            rate: $rate,
            taxCountry: $transaction->buyerCountry,
            ruleApplied: 'ioss',
            reasoning: "Import of goods into EU via IOSS — buyer country {$transaction->buyerCountry->code} VAT applies",
        );
    }

    public function priority(): int
    {
        return 70;
    }
}
