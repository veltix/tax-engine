<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use DateTimeImmutable;
use Veltix\TaxEngine\Contracts\OssTurnoverRepositoryContract;
use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class OssRule implements RuleContract
{
    public function __construct(
        private RateRepositoryContract $rates,
        private bool $ossEnabled = false,
        private int $ossThresholdCents = 1000000,
        private ?OssTurnoverRepositoryContract $turnoverRepository = null,
    ) {}

    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        if (! $this->ossEnabled) {
            return false;
        }

        if (! $transaction->isCrossBorderEu() || ! $transaction->isB2C()) {
            return false;
        }

        if ($this->turnoverRepository !== null) {
            $turnover = $this->turnoverRepository->rollingTwelveMonthTurnoverCents(
                $transaction->sellerCountry,
                new DateTimeImmutable(),
            );

            if ($turnover < $this->ossThresholdCents) {
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
            scheme: TaxScheme::OSS,
            rate: $rate,
            taxCountry: $transaction->buyerCountry,
            ruleApplied: 'oss',
            reasoning: "B2C cross-border EU sale with OSS — buyer country {$transaction->buyerCountry->code} VAT applies",
        );
    }

    public function priority(): int
    {
        return 40;
    }
}
