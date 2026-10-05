<?php

declare(strict_types=1);

use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\EuVatRates;
use Veltix\TaxEngine\Support\VersionInfo;

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

it('is the 2026.1 rate table', function () {
    expect(EuVatRates::version())->toBe('2026.1')
        ->and(VersionInfo::rateDatasetVersion())->toBe('2026.1');
});

it('pins the standard rate of every member state as of 2026-10-05', function (string $code, string $expected) {
    expect(EuVatRates::standardRate($code))->toBe($expected)
        ->and((new Country($code))->standardVatRate())->toBe($expected);
})->with([
    ['AT', '20.00'], ['BE', '21.00'], ['BG', '20.00'], ['HR', '25.00'], ['CY', '19.00'],
    ['CZ', '21.00'], ['DK', '25.00'], ['EE', '24.00'], ['FI', '25.50'], ['FR', '20.00'],
    ['DE', '19.00'], ['GR', '24.00'], ['HU', '27.00'], ['IE', '23.00'], ['IT', '22.00'],
    ['LV', '21.00'], ['LT', '21.00'], ['LU', '17.00'], ['MT', '18.00'], ['NL', '21.00'],
    ['PL', '23.00'], ['PT', '23.00'], ['RO', '21.00'], ['SK', '23.00'], ['SI', '22.00'],
    ['ES', '21.00'], ['SE', '25.00'],
]);

it('pins the rates that changed in the 2026.1 table', function (string $code, array $expected) {
    expect(EuVatRates::reducedRates($code))->toBe($expected);
})->with([
    'Austria: 4.9 % on basic foodstuffs from 2026-07-01' => ['AT', ['reduced' => '10.00', 'reduced_second' => '13.00', 'super_reduced' => '4.90', 'parking' => '13.00']],
    'Cyprus: 3 % super-reduced rate' => ['CY', ['reduced' => '5.00', 'reduced_second' => '9.00', 'super_reduced' => '3.00']],
    'Estonia: 13 % on accommodation from 2025-01-01' => ['EE', ['reduced' => '9.00', 'reduced_second' => '13.00']],
    'Finland: 14 % lowered to 13.5 % from 2026-01-01' => ['FI', ['reduced' => '10.00', 'reduced_second' => '13.50']],
    'Lithuania: 9 % raised to 12 % from 2026-01-01' => ['LT', ['reduced' => '5.00', 'reduced_second' => '12.00']],
    'Malta: 12 % parking rate' => ['MT', ['reduced' => '5.00', 'reduced_second' => '7.00', 'parking' => '12.00']],
    'Romania: 5 % and 9 % merged into 11 % from 2025-08-01' => ['RO', ['reduced' => '11.00']],
    'Slovakia: 10 % replaced by 5 % and 19 % from 2025-01-01' => ['SK', ['reduced' => '5.00', 'reduced_second' => '19.00']],
]);

it('resolves Romania at 21 % standard and 11 % reduced', function () {
    expect(EuVatRates::rateForSupplyType('RO', 'goods'))->toBe('21.00')
        ->and(EuVatRates::rateForSupplyType('RO', 'goods', 'reduced'))->toBe('11.00')
        ->and(EuVatRates::rateForSupplyType('RO', 'goods', 'reduced_second'))->toBe('21.00');
});

it('resolves Slovakia and Finland reduced rates', function () {
    expect(EuVatRates::rateForSupplyType('SK', 'goods', 'reduced'))->toBe('5.00')
        ->and(EuVatRates::rateForSupplyType('SK', 'goods', 'reduced_second'))->toBe('19.00')
        ->and(EuVatRates::rateForSupplyType('FI', 'goods', 'reduced_second'))->toBe('13.50');
});
