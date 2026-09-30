<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Http;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Exceptions\VatValidationException;
use Veltix\TaxEngine\Services\CachingVatValidator;
use Veltix\TaxEngine\Services\ViesVatValidator;

function createArrayCache(): CacheRepository
{
    return new CacheRepository(new ArrayStore());
}

test('caches result and returns it on second call', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->with('DE', '123456789')
        ->andReturn(VatValidationResultData::validResult('DE', '123456789'));

    $cache = createArrayCache();
    $validator = new CachingVatValidator($inner, $cache, 3600);

    $first = $validator->validate('DE', '123456789');
    $second = $validator->validate('DE', '123456789');

    expect($first->valid)->toBeTrue()
        ->and($second->valid)->toBeTrue();
});

test('caches invalid results too', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->andReturn(VatValidationResultData::invalid('DE', '12345', 'Invalid'));

    $cache = createArrayCache();
    $validator = new CachingVatValidator($inner, $cache, 3600);

    $first = $validator->validate('DE', '12345');
    $second = $validator->validate('DE', '12345');

    expect($first->valid)->toBeFalse()
        ->and($second->valid)->toBeFalse();
});

test('uses correct cache key format', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->once()
        ->andReturn(VatValidationResultData::validResult('NL', '123456789B01'));

    $cache = createArrayCache();
    $validator = new CachingVatValidator($inner, $cache, 3600);

    $validator->validate('NL', '123456789B01');

    expect($cache->has('tax_engine:vat_validation:NL:123456789B01'))->toBeTrue();
});

test('does not cache exceptions', function () {
    $callCount = 0;
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->twice()
        ->andReturnUsing(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw VatValidationException::serviceUnavailable();
            }

            return VatValidationResultData::validResult('DE', '123456789');
        });

    $cache = createArrayCache();
    $validator = new CachingVatValidator($inner, $cache, 3600);

    try {
        $validator->validate('DE', '123456789');
    } catch (VatValidationException) {
        // Expected
    }

    $result = $validator->validate('DE', '123456789');
    expect($result->valid)->toBeTrue();
});

test('refresh asks again while an answer is cached and caches the new answer', function () {
    $inner = Mockery::mock(VatValidatorContract::class);
    $inner->shouldReceive('validate')
        ->twice()
        ->with('DE', '123456789')
        ->andReturn(
            VatValidationResultData::validResult('DE', '123456789'),
            VatValidationResultData::invalid('DE', '123456789', 'Deregistered'),
        );

    $validator = new CachingVatValidator($inner, createArrayCache(), 3600);

    expect($validator->validate('DE', '123456789')->valid)->toBeTrue()
        ->and($validator->refresh('DE', '123456789')->valid)->toBeFalse()
        ->and($validator->validate('DE', '123456789')->valid)->toBeFalse();
});

test('does not cache a member state VIES could not reach, and recovers on the next call', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::sequence()
            ->push(['valid' => false, 'userError' => 'MS_UNAVAILABLE'])
            ->push(['valid' => true, 'userError' => 'VALID']),
    ]);

    $cache = createArrayCache();
    $validator = new CachingVatValidator(new ViesVatValidator(), $cache, 3600);

    expect(fn () => $validator->validate('DE', '123456789'))->toThrow(VatValidationException::class)
        ->and($cache->has('tax_engine:vat_validation:DE:123456789'))->toBeFalse()
        ->and($validator->validate('DE', '123456789')->valid)->toBeTrue();
});
