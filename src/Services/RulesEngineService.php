<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\PriceMode;
use Veltix\TaxEngine\Enums\RoundingMode;
use Veltix\TaxEngine\Exceptions\NoApplicableRuleException;
use Veltix\TaxEngine\Support\Money;

final class RulesEngineService
{
    /** @var RuleContract[] */
    private array $rules = [];

    private RoundingMode $roundingMode;

    public function __construct(RoundingMode $roundingMode = RoundingMode::HalfUp)
    {
        $this->roundingMode = $roundingMode;
    }

    public function addRule(RuleContract $rule): void
    {
        $this->rules[] = $rule;
    }

    public function decide(TransactionData $transaction, ?TaxCalculationContext $context = null): TaxDecisionData
    {
        $context ??= new TaxCalculationContext();
        $sorted = $this->sortedRules();

        foreach ($sorted as $rule) {
            if ($rule->applies($transaction, $context)) {
                return $rule->evaluate($transaction, $context);
            }
        }

        throw NoApplicableRuleException::forTransaction($transaction->transactionId);
    }

    public function calculate(TransactionData $transaction, ?TaxCalculationContext $context = null): TaxResultData
    {
        $context ??= new TaxCalculationContext();
        $decision = $this->decide($transaction, $context);

        if ($context->priceMode === PriceMode::TaxInclusive) {
            return $this->calculateTaxInclusive($transaction, $decision);
        }

        $taxAmount = $transaction->amount->allocateTax($decision->rate, $this->roundingMode);
        $grossAmount = $transaction->amount->add($taxAmount);

        return new TaxResultData(
            netAmount: $transaction->amount,
            taxAmount: $taxAmount,
            grossAmount: $grossAmount,
            decision: $decision,
            transactionId: $transaction->transactionId,
        );
    }

    private function calculateTaxInclusive(TransactionData $transaction, TaxDecisionData $decision): TaxResultData
    {
        $grossAmount = $transaction->amount;
        $rateFactor = bcdiv($decision->rate, '100', 10);
        $divisor = bcadd('1', $rateFactor, 10);
        $netCents = bcdiv((string) $grossAmount->amount, $divisor, 10);
        $netCentsRounded = (int) round((float) $netCents);
        $taxCents = $grossAmount->amount - $netCentsRounded;

        return new TaxResultData(
            netAmount: Money::fromCents($netCentsRounded, $grossAmount->currency),
            taxAmount: Money::fromCents($taxCents, $grossAmount->currency),
            grossAmount: $grossAmount,
            decision: $decision,
            transactionId: $transaction->transactionId,
        );
    }

    /**
     * @return RuleContract[]
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function roundingMode(): RoundingMode
    {
        return $this->roundingMode;
    }

    /**
     * @return RuleContract[]
     */
    private function sortedRules(): array
    {
        $rules = $this->rules;

        usort($rules, fn (RuleContract $a, RuleContract $b) => $b->priority() <=> $a->priority());

        return $rules;
    }
}
