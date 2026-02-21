<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\LegalEntityData;
use Veltix\TaxEngine\Support\Country;

it('creates with defaults', function () {
    $entity = new LegalEntityData(country: new Country('NL'));

    expect($entity->country->code)->toBe('NL')
        ->and($entity->vatNumber)->toBeNull()
        ->and($entity->ossRegistered)->toBeFalse()
        ->and($entity->iossRegistered)->toBeFalse();
});

it('creates with all parameters', function () {
    $entity = new LegalEntityData(
        country: new Country('DE'),
        vatNumber: 'DE123456789',
        ossRegistered: true,
        iossRegistered: true,
    );

    expect($entity->country->code)->toBe('DE')
        ->and($entity->vatNumber)->toBe('DE123456789')
        ->and($entity->ossRegistered)->toBeTrue()
        ->and($entity->iossRegistered)->toBeTrue();
});

it('serializes to array', function () {
    $entity = new LegalEntityData(
        country: new Country('NL'),
        vatNumber: 'NL123456789B01',
        ossRegistered: true,
    );

    $array = $entity->toArray();

    expect($array)->toBe([
        'country' => 'NL',
        'vatNumber' => 'NL123456789B01',
        'ossRegistered' => true,
        'iossRegistered' => false,
    ]);
});
