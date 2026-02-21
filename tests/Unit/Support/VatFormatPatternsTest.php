<?php

declare(strict_types=1);

use Veltix\TaxEngine\Support\VatFormatPatterns;

test('hasPattern returns true for EU countries', function (string $country) {
    expect(VatFormatPatterns::hasPattern($country))->toBeTrue();
})->with([
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
    'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
    'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
]);

test('hasPattern returns false for non-EU countries', function (string $country) {
    expect(VatFormatPatterns::hasPattern($country))->toBeFalse();
})->with(['US', 'GB', 'CH', 'NO', 'JP']);

test('getPattern returns regex for EU country', function () {
    expect(VatFormatPatterns::getPattern('DE'))->toBe('/^\d{9}$/');
});

test('getPattern returns null for non-EU country', function () {
    expect(VatFormatPatterns::getPattern('US'))->toBeNull();
});

test('viesPrefix maps GR to EL', function () {
    expect(VatFormatPatterns::viesPrefix('GR'))->toBe('EL');
});

test('viesPrefix returns same code for non-mapped countries', function () {
    expect(VatFormatPatterns::viesPrefix('DE'))->toBe('DE')
        ->and(VatFormatPatterns::viesPrefix('NL'))->toBe('NL');
});

test('hasPattern is case insensitive', function () {
    expect(VatFormatPatterns::hasPattern('de'))->toBeTrue()
        ->and(VatFormatPatterns::hasPattern('De'))->toBeTrue();
});
