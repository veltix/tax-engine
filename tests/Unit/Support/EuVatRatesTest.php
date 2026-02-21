<?php

declare(strict_types=1);

use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\EuVatRates;

it('returns standard rates for all 27 EU members', function () {
    foreach (Country::EU_MEMBERS as $code) {
        $rate = EuVatRates::standardRate($code);
        expect($rate)->not->toBeNull("Standard rate missing for {$code}")
            ->and($rate)->toBe(Country::STANDARD_RATES[$code]);
    }
});

it('returns null standard rate for non-EU country', function () {
    expect(EuVatRates::standardRate('US'))->toBeNull();
});

it('returns reduced rates for countries with them', function (string $code, array $expectedKeys) {
    $rates = EuVatRates::reducedRates($code);

    expect($rates)->toHaveKeys($expectedKeys);
})->with([
    ['DE', ['reduced']],
    ['FR', ['reduced', 'reduced_second', 'super_reduced']],
    ['IE', ['reduced', 'reduced_second', 'super_reduced', 'parking']],
    ['LU', ['reduced', 'super_reduced', 'parking']],
    ['AT', ['reduced', 'reduced_second', 'parking']],
]);

it('returns empty array for Denmark (no reduced rates)', function () {
    expect(EuVatRates::reducedRates('DK'))->toBe([]);
});

it('returns empty array for non-EU country reduced rates', function () {
    expect(EuVatRates::reducedRates('US'))->toBe([]);
});

it('returns correct reduced rate values', function (string $code, string $key, string $expected) {
    $rates = EuVatRates::reducedRates($code);

    expect($rates[$key])->toBe($expected);
})->with([
    ['DE', 'reduced', '7.00'],
    ['FR', 'reduced', '5.50'],
    ['FR', 'super_reduced', '2.10'],
    ['IE', 'parking', '13.50'],
    ['LU', 'super_reduced', '3.00'],
    ['ES', 'super_reduced', '4.00'],
    ['IT', 'super_reduced', '4.00'],
]);

it('resolves standard rate for supply type without category', function () {
    expect(EuVatRates::rateForSupplyType('DE', 'digital_services'))->toBe('19.00')
        ->and(EuVatRates::rateForSupplyType('FR', 'goods'))->toBe('20.00')
        ->and(EuVatRates::rateForSupplyType('DE', 'telecom'))->toBe('19.00')
        ->and(EuVatRates::rateForSupplyType('DE', 'broadcasting'))->toBe('19.00');
});

it('resolves category override for supply type', function () {
    expect(EuVatRates::rateForSupplyType('DE', 'goods', 'reduced'))->toBe('7.00')
        ->and(EuVatRates::rateForSupplyType('FR', 'goods', 'super_reduced'))->toBe('2.10')
        ->and(EuVatRates::rateForSupplyType('IE', 'goods', 'parking'))->toBe('13.50');
});

it('falls back to standard rate when category not found', function () {
    expect(EuVatRates::rateForSupplyType('DE', 'goods', 'super_reduced'))->toBe('19.00')
        ->and(EuVatRates::rateForSupplyType('DK', 'goods', 'reduced'))->toBe('25.00');
});
