<?php

declare(strict_types=1);

use Illuminate\Contracts\Events\Dispatcher;
use Veltix\TaxEngine\Actions\CalculateInvoiceTaxAction;
use Veltix\TaxEngine\Data\InvoiceData;
use Veltix\TaxEngine\Data\InvoiceLineData;
use Veltix\TaxEngine\Data\InvoiceResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Events\InvoiceTaxCalculated;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Rules\ServiceExportRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function buildInvoiceAction(?Dispatcher $events = null): CalculateInvoiceTaxAction
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ExportRule());
    $engine->addRule(new ServiceExportRule());
    $engine->addRule(new ReverseChargeRule());
    $engine->addRule(new DomesticStandardRule($rates));

    return new CalculateInvoiceTaxAction(
        rulesEngine: $engine,
        events: $events,
    );
}

it('calculates 10 x EUR 1.45 at 21% with total tax 305 not 300', function () {
    $action = buildInvoiceAction();

    $lines = [];
    for ($i = 1; $i <= 10; $i++) {
        $lines[] = new InvoiceLineData(
            lineId: "line-{$i}",
            amount: Money::fromCents(145),
            supplyType: SupplyType::Goods,
        );
    }

    $invoice = new InvoiceData(
        invoiceId: 'inv-rounding-001',
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        lines: $lines,
    );

    $result = $action->execute($invoice);

    expect($result)->toBeInstanceOf(InvoiceResultData::class)
        ->and($result->invoiceId)->toBe('inv-rounding-001')
        ->and($result->totalNet->amount)->toBe(1450)
        ->and($result->totalTax->amount)->toBe(305)
        ->and($result->totalGross->amount)->toBe(1755)
        ->and($result->lineResults)->toHaveCount(10);

    // Verify allocations sum correctly
    $allocatedSum = 0;
    foreach ($result->lineResults as $lr) {
        $allocatedSum += $lr->allocatedTax->amount;
    }
    expect($allocatedSum)->toBe(305);

    // Each line should get either 30 or 31 cents tax
    foreach ($result->lineResults as $lr) {
        expect($lr->allocatedTax->amount)->toBeIn([30, 31]);
    }
});

it('single line matches CalculateTaxAction output', function () {
    $action = buildInvoiceAction();

    $invoice = new InvoiceData(
        invoiceId: 'inv-single',
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'only-line',
                amount: Money::fromCents(10000),
                supplyType: SupplyType::Goods,
            ),
        ],
    );

    $result = $action->execute($invoice);

    // DE domestic B2C goods = 19% standard
    expect($result->totalNet->amount)->toBe(10000)
        ->and($result->totalTax->amount)->toBe(1900)
        ->and($result->totalGross->amount)->toBe(11900)
        ->and($result->lineResults[0]->allocatedTax->amount)->toBe(1900)
        ->and($result->lineResults[0]->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->lineResults[0]->decision->rate)->toBe('19.00');
});

it('fires InvoiceTaxCalculated event', function () {
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(InvoiceTaxCalculated::class));

    $action = buildInvoiceAction(events: $dispatcher);

    $invoice = new InvoiceData(
        invoiceId: 'inv-event',
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-1',
                amount: Money::fromCents(5000),
                supplyType: SupplyType::Goods,
            ),
        ],
    );

    $action->execute($invoice);
});

it('works without event dispatcher', function () {
    $action = buildInvoiceAction();

    $invoice = new InvoiceData(
        invoiceId: 'inv-no-events',
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-1',
                amount: Money::fromCents(1000),
                supplyType: SupplyType::Goods,
            ),
        ],
    );

    $result = $action->execute($invoice);

    expect($result->invoiceId)->toBe('inv-no-events')
        ->and($result->totalTax->amount)->toBe(190);
});

it('builds tax summary keyed by rate', function () {
    $action = buildInvoiceAction();

    $invoice = new InvoiceData(
        invoiceId: 'inv-summary',
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-1',
                amount: Money::fromCents(1000),
                supplyType: SupplyType::Goods,
            ),
            new InvoiceLineData(
                lineId: 'line-2',
                amount: Money::fromCents(2000),
                supplyType: SupplyType::Goods,
            ),
        ],
    );

    $result = $action->execute($invoice);

    // NL rate is 21%: (1000+2000) * 0.21 = 630
    expect($result->taxSummary)->toHaveKey('21.00')
        ->and($result->taxSummary['21.00']->amount)->toBe(630);
});
