<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Veltix\TaxEngine\Contracts\RuleContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;

final readonly class ServiceExportRule implements RuleContract
{
    public function applies(TransactionData $transaction, TaxCalculationContext $context): bool
    {
        return $transaction->isExport()
            && $transaction->supplyType !== SupplyType::Goods;
    }

    public function evaluate(TransactionData $transaction, TaxCalculationContext $context): TaxDecisionData
    {
        return new TaxDecisionData(
            scheme: TaxScheme::OutsideScope,
            rate: '0.00',
            taxCountry: $transaction->sellerCountry,
            ruleApplied: 'service_export',
            reasoning: "Service export from {$transaction->sellerCountry->code} to non-EU {$transaction->buyerCountry->code} — outside scope of EU VAT",
        );
    }

    public function priority(): int
    {
        return 59;
    }
}
