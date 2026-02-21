<?php

declare(strict_types=1);

use Veltix\TaxEngine\Services\NullVatValidator;

test('always returns valid result', function () {
    $validator = new NullVatValidator();

    $result = $validator->validate('DE', '123456789');

    expect($result->valid)->toBeTrue()
        ->and($result->countryCode)->toBe('DE')
        ->and($result->vatNumber)->toBe('123456789');
});

test('returns valid for any country and number', function () {
    $validator = new NullVatValidator();

    expect($validator->validate('US', 'anything')->valid)->toBeTrue()
        ->and($validator->validate('NL', '123456789B01')->valid)->toBeTrue();
});
