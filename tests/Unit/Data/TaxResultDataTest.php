<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('constructs with all properties', function () {
    $result = makeResult(net: 10000, tax: 2100, gross: 12100);

    expect($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(2100)
        ->and($result->grossAmount->amount)->toBe(12100)
        ->and($result->transactionId)->toBe('txn-001');
});

it('calculates effective rate correctly', function () {
    expect(makeResult(net: 10000, tax: 2100, gross: 12100)->effectiveRate())->toBe('21.00');
});

it('handles zero net in effective rate', function () {
    expect(makeResult(net: 0, tax: 0, gross: 0)->effectiveRate())->toBe('0.00');
});

it('calculates non-standard effective rates', function () {
    expect(makeResult(net: 10000, tax: 1900, gross: 11900)->effectiveRate())->toBe('19.00');
});

it('includes all fields in toArray', function () {
    $array = makeResult(net: 10000, tax: 2100, gross: 12100)->toArray();

    expect($array)
        ->toHaveKeys(['netAmount', 'taxAmount', 'grossAmount', 'decision', 'transactionId', 'effectiveRate'])
        ->and($array['effectiveRate'])->toBe('21.00');
});

function makeResult(int $net, int $tax, int $gross): TaxResultData
{
    $decision = new TaxDecisionData(
        scheme: TaxScheme::Standard,
        rate: '21.00',
        taxCountry: new Country('NL'),
        ruleApplied: 'TestRule',
        reasoning: 'Test',
    );

    return new TaxResultData(
        netAmount: Money::fromCents($net),
        taxAmount: Money::fromCents($tax),
        grossAmount: Money::fromCents($gross),
        decision: $decision,
        transactionId: 'txn-001',
    );
}
