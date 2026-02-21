<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Events\TaxCalculated;
use Veltix\TaxEngine\Services\RulesEngineService;

final class CalculateTaxAction
{
    public function __construct(
        private readonly RulesEngineService $rulesEngine,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function execute(
        TransactionData $transaction,
        ?VatValidationResultData $vatResult = null,
        array $metadata = [],
        ?TaxCalculationContext $context = null,
    ): TaxResultData {
        return $this->calculate($transaction, $vatResult, $metadata, $context);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function calculate(
        TransactionData $transaction,
        ?VatValidationResultData $vatResult = null,
        array $metadata = [],
        ?TaxCalculationContext $context = null,
    ): TaxResultData {
        $context ??= new TaxCalculationContext(vatResult: $vatResult);

        $result = $this->rulesEngine->calculate($transaction, $context);

        $this->events?->dispatch(new TaxCalculated(
            transaction: $transaction,
            result: $result,
            vatResult: $vatResult,
            metadata: $metadata,
        ));

        return $result;
    }
}
