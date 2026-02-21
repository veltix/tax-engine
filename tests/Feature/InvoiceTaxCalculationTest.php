<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateInvoiceTaxAction;
use Veltix\TaxEngine\Data\InvoiceData;
use Veltix\TaxEngine\Data\InvoiceLineData;
use Veltix\TaxEngine\Data\InvoiceResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('can resolve CalculateInvoiceTaxAction from the container', function () {
    $action = app(CalculateInvoiceTaxAction::class);

    expect($action)->toBeInstanceOf(CalculateInvoiceTaxAction::class);
});

it('calculates invoice tax end-to-end via the container', function () {
    $action = app(CalculateInvoiceTaxAction::class);

    $lines = [];
    for ($i = 1; $i <= 10; $i++) {
        $lines[] = new InvoiceLineData(
            lineId: "line-{$i}",
            amount: Money::fromCents(145),
            supplyType: SupplyType::Goods,
        );
    }

    $invoice = new InvoiceData(
        invoiceId: 'inv-feature-001',
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        lines: $lines,
    );

    $result = $action->execute($invoice);

    expect($result)->toBeInstanceOf(InvoiceResultData::class)
        ->and($result->totalNet->amount)->toBe(1450)
        ->and($result->totalTax->amount)->toBe(305)
        ->and($result->totalGross->amount)->toBe(1755);
});

it('handles single line invoice via the container', function () {
    $action = app(CalculateInvoiceTaxAction::class);

    $invoice = new InvoiceData(
        invoiceId: 'inv-feature-single',
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-1',
                amount: Money::fromCents(10000),
                supplyType: SupplyType::Goods,
            ),
        ],
    );

    $result = $action->execute($invoice);

    expect($result->totalTax->amount)->toBe(1900)
        ->and($result->totalGross->amount)->toBe(11900);
});
