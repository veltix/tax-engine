<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Veltix\TaxEngine\Data\InvoiceData;
use Veltix\TaxEngine\Data\InvoiceLineResultData;
use Veltix\TaxEngine\Data\InvoiceResultData;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Events\InvoiceTaxCalculated;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\LargestRemainderAllocator;
use Veltix\TaxEngine\Support\Money;

final class CalculateInvoiceTaxAction
{
    public function __construct(
        private readonly RulesEngineService $rulesEngine,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function execute(InvoiceData $invoice, ?TaxCalculationContext $context = null): InvoiceResultData
    {
        $context ??= new TaxCalculationContext();

        // Step 1: Decide tax for each line
        /** @var array<int, TaxDecisionData> */
        $decisions = [];
        foreach ($invoice->lines as $index => $line) {
            $transaction = $invoice->toTransactionData($line);
            $decisions[$index] = $this->rulesEngine->decide($transaction, $context);
        }

        // Step 2: Group lines by rate
        /** @var array<string, int[]> */
        $rateGroups = [];
        foreach ($decisions as $index => $decision) {
            $rateGroups[$decision->rate][] = $index;
        }

        // Step 3: Per rate group, compute group tax and allocate via largest remainder
        $roundingMode = $this->rulesEngine->roundingMode();
        /** @var array<int, int> $allocatedTaxCents */
        $allocatedTaxCents = [];
        $currency = $invoice->lines[0]->amount->currency;
        $precision = $invoice->lines[0]->amount->precision;

        foreach ($rateGroups as $rate => $indices) {
            // Sum net amounts for the group
            $groupNetCents = 0;
            foreach ($indices as $idx) {
                $groupNetCents += $invoice->lines[$idx]->amount->amount;
            }

            $groupNet = Money::fromCents($groupNetCents, $currency, $precision);
            $groupTax = $groupNet->allocateTax((string) $rate, $roundingMode);

            // Compute exact unrounded tax per line in the group
            $rateFactor = bcdiv((string) $rate, '100', 10);
            $exactFractionals = [];
            foreach ($indices as $idx) {
                $exactFractionals[$idx] = bcmul(
                    (string) $invoice->lines[$idx]->amount->amount,
                    $rateFactor,
                    10,
                );
            }

            $allocated = LargestRemainderAllocator::allocate(
                $groupTax->amount,
                $exactFractionals,
            );

            foreach ($allocated as $idx => $cents) {
                $allocatedTaxCents[$idx] = $cents;
            }
        }

        // Step 4: Build line results
        $lineResults = [];
        $totalNet = Money::zero($currency, $precision);
        $totalTax = Money::zero($currency, $precision);
        /** @var array<string, Money> */
        $taxSummary = [];

        foreach ($invoice->lines as $index => $line) {
            $taxMoney = Money::fromCents($allocatedTaxCents[$index], $currency, $precision);
            $gross = $line->amount->add($taxMoney);

            $lineResults[] = new InvoiceLineResultData(
                lineId: $line->lineId,
                netAmount: $line->amount,
                allocatedTax: $taxMoney,
                grossAmount: $gross,
                decision: $decisions[$index],
            );

            $totalNet = $totalNet->add($line->amount);
            $totalTax = $totalTax->add($taxMoney);

            $rate = $decisions[$index]->rate;
            $taxSummary[$rate] = isset($taxSummary[$rate])
                ? $taxSummary[$rate]->add($taxMoney)
                : $taxMoney;
        }

        $result = new InvoiceResultData(
            invoiceId: $invoice->invoiceId,
            lineResults: $lineResults,
            totalNet: $totalNet,
            totalTax: $totalTax,
            totalGross: $totalNet->add($totalTax),
            taxSummary: $taxSummary,
        );

        // Step 5: Fire event
        $this->events?->dispatch(new InvoiceTaxCalculated(
            invoice: $invoice,
            result: $result,
        ));

        return $result;
    }
}
