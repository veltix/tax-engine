<?php

declare(strict_types=1);

use Veltix\TaxEngine\Exceptions\RateNotFoundException;

it('creates exception for country', function () {
    $exception = RateNotFoundException::forCountry('US');

    expect($exception)
        ->toBeInstanceOf(RateNotFoundException::class)
        ->toBeInstanceOf(RuntimeException::class)
        ->getMessage()->toBe('No VAT rate found for country: US');
});

it('creates exception for supply type', function () {
    $exception = RateNotFoundException::forSupplyType('US', 'digital_services');

    expect($exception)
        ->toBeInstanceOf(RateNotFoundException::class)
        ->getMessage()->toBe("No VAT rate found for supply type 'digital_services' in country: US");
});
