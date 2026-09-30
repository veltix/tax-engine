<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Services\CachingVatValidator;
use Veltix\TaxEngine\Services\VatValidatorService;

test('returns invalid for bad format without calling driver', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldNotReceive('validate');

    $service = new VatValidatorService($inner);
    $result = $service->validate('DE12345', null);

    expect($result->valid)->toBeFalse()
        ->and($result->formatValid)->toBeFalse();
});

test('returns invalid for non-EU country without calling driver', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldNotReceive('validate');

    $service = new VatValidatorService($inner);
    $result = $service->validate('US123456789', null);

    expect($result->valid)->toBeFalse()
        ->and($result->failureReason)->toContain('not an EU member');
});

test('delegates to driver for valid EU VAT number', function () {
    $expected = VatValidationResultData::validResult('DE', '123456789');

    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->with('DE', '123456789')
        ->andReturn($expected);

    $service = new VatValidatorService($inner);
    $result = $service->validate('DE123456789');

    expect($result->valid)->toBeTrue()
        ->and($result->countryCode)->toBe('DE');
});

test('handles explicit country code parameter', function () {
    $expected = VatValidationResultData::validResult('NL', '123456789B01');

    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->with('NL', '123456789B01')
        ->andReturn($expected);

    $service = new VatValidatorService($inner);
    $result = $service->validate('123456789B01', 'NL');

    expect($result->valid)->toBeTrue();
});

test('returns invalid when country code cannot be determined', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldNotReceive('validate');

    $service = new VatValidatorService($inner);
    $result = $service->validate('123456789');

    expect($result->valid)->toBeFalse()
        ->and($result->failureReason)->toContain('Could not determine');
});

test('a fresh check asks past the cache and a normal one reads it', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->twice()
        ->with('DE', '123456789')
        ->andReturn(
            VatValidationResultData::validResult('DE', '123456789'),
            VatValidationResultData::invalid('DE', '123456789', 'Deregistered'),
        );

    $service = new VatValidatorService(new CachingVatValidator($inner, new CacheRepository(new ArrayStore()), 3600));

    expect($service->validate('DE123456789')->valid)->toBeTrue()
        ->and($service->validate('DE123456789')->valid)->toBeTrue()
        ->and($service->validate('DE123456789', fresh: true)->valid)->toBeFalse()
        ->and($service->validate('DE123456789')->valid)->toBeFalse();
});

test('a fresh check asks an uncached validator as usual', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->with('DE', '123456789')
        ->andReturn(VatValidationResultData::validResult('DE', '123456789'));

    expect((new VatValidatorService($inner))->validate('DE123456789', fresh: true)->valid)->toBeTrue();
});
