<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\VatValidationResultData;

test('can be constructed with all properties', function () {
    $date = new DateTimeImmutable();
    $result = new VatValidationResultData(
        valid: true,
        countryCode: 'DE',
        vatNumber: '123456789',
        name: 'Test GmbH',
        address: 'Berlin, Germany',
        requestDate: $date,
        formatValid: true,
        failureReason: null,
    );

    expect($result->valid)->toBeTrue()
        ->and($result->countryCode)->toBe('DE')
        ->and($result->vatNumber)->toBe('123456789')
        ->and($result->name)->toBe('Test GmbH')
        ->and($result->address)->toBe('Berlin, Germany')
        ->and($result->requestDate)->toBe($date)
        ->and($result->formatValid)->toBeTrue()
        ->and($result->failureReason)->toBeNull();
});

test('formatValid defaults to true', function () {
    $result = new VatValidationResultData(
        valid: true,
        countryCode: 'NL',
        vatNumber: '123456789B01',
    );

    expect($result->formatValid)->toBeTrue();
});

test('invalid named constructor creates invalid result', function () {
    $result = VatValidationResultData::invalid('DE', '12345', 'Invalid format', formatValid: false);

    expect($result->valid)->toBeFalse()
        ->and($result->countryCode)->toBe('DE')
        ->and($result->vatNumber)->toBe('12345')
        ->and($result->failureReason)->toBe('Invalid format')
        ->and($result->formatValid)->toBeFalse()
        ->and($result->requestDate)->toBeInstanceOf(DateTimeImmutable::class);
});

test('validResult named constructor creates valid result', function () {
    $result = VatValidationResultData::validResult('NL', '123456789B01', 'Company BV', 'Amsterdam');

    expect($result->valid)->toBeTrue()
        ->and($result->countryCode)->toBe('NL')
        ->and($result->vatNumber)->toBe('123456789B01')
        ->and($result->name)->toBe('Company BV')
        ->and($result->address)->toBe('Amsterdam')
        ->and($result->requestDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($result->failureReason)->toBeNull();
});

test('toArray serializes all fields', function () {
    $result = VatValidationResultData::validResult('DE', '123456789', 'Test GmbH');

    $array = $result->toArray();

    expect($array)->toHaveKeys([
        'valid', 'countryCode', 'vatNumber', 'name', 'address',
        'requestDate', 'formatValid', 'failureReason',
    ])
        ->and($array['valid'])->toBeTrue()
        ->and($array['countryCode'])->toBe('DE')
        ->and($array['vatNumber'])->toBe('123456789')
        ->and($array['name'])->toBe('Test GmbH')
        ->and($array['requestDate'])->toBeString();
});
