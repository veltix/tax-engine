<?php

declare(strict_types=1);

use Veltix\TaxEngine\Services\VatFormatValidator;

test('parse extracts country code from prefixed VAT number', function () {
    [$country, $number] = VatFormatValidator::parse('DE123456789');

    expect($country)->toBe('DE')
        ->and($number)->toBe('123456789');
});

test('parse uses explicit country code when provided', function () {
    [$country, $number] = VatFormatValidator::parse('123456789', 'DE');

    expect($country)->toBe('DE')
        ->and($number)->toBe('123456789');
});

test('parse maps EL prefix to GR country code', function () {
    [$country, $number] = VatFormatValidator::parse('EL123456789');

    expect($country)->toBe('GR')
        ->and($number)->toBe('123456789');
});

test('parse strips whitespace dots and dashes', function () {
    [$country, $number] = VatFormatValidator::parse('NL 123.456.789-B01');

    expect($country)->toBe('NL')
        ->and($number)->toBe('123456789B01');
});

test('parse handles lowercase prefix', function () {
    [$country, $number] = VatFormatValidator::parse('de123456789');

    expect($country)->toBe('DE')
        ->and($number)->toBe('123456789');
});

test('isValidFormat returns true for valid EU VAT numbers', function (string $country, string $number) {
    expect(VatFormatValidator::isValidFormat($country, $number))->toBeTrue();
})->with([
    ['AT', 'U12345678'],
    ['BE', '0123456789'],
    ['BG', '123456789'],
    ['BG', '1234567890'],
    ['HR', '12345678901'],
    ['CY', '12345678A'],
    ['CZ', '12345678'],
    ['CZ', '123456789'],
    ['CZ', '1234567890'],
    ['DK', '12345678'],
    ['EE', '123456789'],
    ['FI', '12345678'],
    ['FR', 'AB123456789'],
    ['FR', '12123456789'],
    ['DE', '123456789'],
    ['GR', '123456789'],
    ['HU', '12345678'],
    ['IE', '1234567A'],
    ['IE', '1234567AB'],
    ['IT', '12345678901'],
    ['LV', '12345678901'],
    ['LT', '123456789'],
    ['LT', '123456789012'],
    ['LU', '12345678'],
    ['MT', '12345678'],
    ['NL', '123456789B01'],
    ['PL', '1234567890'],
    ['PT', '123456789'],
    ['RO', '12'],
    ['RO', '1234567890'],
    ['SK', '1234567890'],
    ['SI', '12345678'],
    ['ES', 'A1234567B'],
    ['ES', 'A12345678'],
    ['ES', '12345678A'],
    ['SE', '123456789012'],
]);

test('isValidFormat rejects invalid EU VAT numbers', function (string $country, string $number) {
    expect(VatFormatValidator::isValidFormat($country, $number))->toBeFalse();
})->with([
    ['DE', '12345'],
    ['DE', '1234567890'],
    ['NL', '123456789'],
    ['AT', '12345678'],
    ['BE', '12345'],
    ['SE', '12345678'],
]);

test('isValidFormat returns true for non-EU countries', function () {
    expect(VatFormatValidator::isValidFormat('US', 'anything'))->toBeTrue()
        ->and(VatFormatValidator::isValidFormat('GB', '123456789'))->toBeTrue();
});
