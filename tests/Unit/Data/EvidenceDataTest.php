<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Enums\EvidenceType;

it('creates empty collection', function () {
    $evidence = EvidenceData::empty();

    expect($evidence->isEmpty())->toBeTrue()
        ->and($evidence->count())->toBe(0);
});

it('creates from items', function () {
    $item1 = EvidenceItemData::billingAddress('DE');
    $item2 = EvidenceItemData::ipAddress('DE');

    $evidence = EvidenceData::fromItems($item1, $item2);

    expect($evidence->count())->toBe(2)
        ->and($evidence->isEmpty())->toBeFalse();
});

it('adds items immutably', function () {
    $original = EvidenceData::empty();
    $item = EvidenceItemData::billingAddress('DE');

    $new = $original->add($item);

    expect($original->count())->toBe(0)
        ->and($new->count())->toBe(1);
});

it('merges two collections immutably', function () {
    $first = EvidenceData::fromItems(EvidenceItemData::billingAddress('DE'));
    $second = EvidenceData::fromItems(EvidenceItemData::ipAddress('FR'));

    $merged = $first->merge($second);

    expect($first->count())->toBe(1)
        ->and($second->count())->toBe(1)
        ->and($merged->count())->toBe(2);
});

it('filters by type with ofType', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
        EvidenceItemData::billingAddress('NL'),
    );

    $billing = $evidence->ofType(EvidenceType::BillingAddress);

    expect($billing)->toHaveCount(2)
        ->and($billing[0]->resolvedCountryCode)->toBe('DE')
        ->and($billing[1]->resolvedCountryCode)->toBe('NL');
});

it('finds first item by type', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    expect($evidence->findByType(EvidenceType::IpAddress)?->resolvedCountryCode)->toBe('FR')
        ->and($evidence->findByType(EvidenceType::BankCountry))->toBeNull();
});

it('returns unique country signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
        EvidenceItemData::bankCountry('FR'),
    );

    $signals = $evidence->countrySignals();

    expect($signals)->toHaveCount(2)
        ->and($signals)->toContain('DE')
        ->and($signals)->toContain('FR');
});

it('counts matching country signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
        EvidenceItemData::bankCountry('FR'),
    );

    expect($evidence->matchingCountryCount())->toBe(2);
});

it('returns 0 matching country count for empty collection', function () {
    expect(EvidenceData::empty()->matchingCountryCount())->toBe(0);
});

it('converts to array', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $array = $evidence->toArray();

    expect($array)->toHaveCount(1)
        ->and($array[0]['type'])->toBe('billing_address')
        ->and($array[0]['resolvedCountryCode'])->toBe('DE');
});
