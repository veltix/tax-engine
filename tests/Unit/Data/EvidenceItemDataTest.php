<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Enums\EvidenceSource;
use Veltix\TaxEngine\Enums\EvidenceType;

it('can be constructed with valid data', function () {
    $item = new EvidenceItemData(
        type: EvidenceType::BillingAddress,
        value: 'DE',
        resolvedCountryCode: 'DE',
        source: EvidenceSource::BillingSystem,
        capturedAt: new DateTimeImmutable('2024-01-01'),
    );

    expect($item->type)->toBe(EvidenceType::BillingAddress)
        ->and($item->value)->toBe('DE')
        ->and($item->resolvedCountryCode)->toBe('DE')
        ->and($item->source)->toBe(EvidenceSource::BillingSystem);
});

it('validates ISO country code in constructor', function () {
    new EvidenceItemData(
        type: EvidenceType::BillingAddress,
        value: 'test',
        resolvedCountryCode: 'INVALID',
        source: EvidenceSource::BillingSystem,
        capturedAt: new DateTimeImmutable(),
    );
})->throws(InvalidArgumentException::class);

it('creates billing address evidence via factory', function () {
    $item = EvidenceItemData::billingAddress('DE');

    expect($item->type)->toBe(EvidenceType::BillingAddress)
        ->and($item->resolvedCountryCode)->toBe('DE')
        ->and($item->source)->toBe(EvidenceSource::BillingSystem);
});

it('creates IP address evidence via factory', function () {
    $item = EvidenceItemData::ipAddress('FR');

    expect($item->type)->toBe(EvidenceType::IpAddress)
        ->and($item->resolvedCountryCode)->toBe('FR')
        ->and($item->source)->toBe(EvidenceSource::GeoIp);
});

it('creates bank country evidence via factory', function () {
    $item = EvidenceItemData::bankCountry('NL');

    expect($item->type)->toBe(EvidenceType::BankCountry)
        ->and($item->resolvedCountryCode)->toBe('NL')
        ->and($item->source)->toBe(EvidenceSource::PaymentProvider);
});

it('creates SIM country evidence via factory', function () {
    $item = EvidenceItemData::simCountry('IT');

    expect($item->type)->toBe(EvidenceType::SimCountry)
        ->and($item->resolvedCountryCode)->toBe('IT')
        ->and($item->source)->toBe(EvidenceSource::PhoneNumber);
});

it('creates payment provider country evidence via factory', function () {
    $item = EvidenceItemData::paymentProviderCountry('ES');

    expect($item->type)->toBe(EvidenceType::PaymentProviderCountry)
        ->and($item->resolvedCountryCode)->toBe('ES')
        ->and($item->source)->toBe(EvidenceSource::PaymentProvider);
});

it('creates shipping address evidence via factory', function () {
    $item = EvidenceItemData::shippingAddress('BE');

    expect($item->type)->toBe(EvidenceType::ShippingAddress)
        ->and($item->resolvedCountryCode)->toBe('BE')
        ->and($item->source)->toBe(EvidenceSource::BillingSystem);
});

it('creates self declared country evidence via factory', function () {
    $item = EvidenceItemData::selfDeclaredCountry('AT');

    expect($item->type)->toBe(EvidenceType::SelfDeclaredCountry)
        ->and($item->resolvedCountryCode)->toBe('AT')
        ->and($item->source)->toBe(EvidenceSource::UserDeclaration);
});

it('converts to array', function () {
    $capturedAt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');
    $item = new EvidenceItemData(
        type: EvidenceType::IpAddress,
        value: '192.168.1.1',
        resolvedCountryCode: 'DE',
        source: EvidenceSource::GeoIp,
        capturedAt: $capturedAt,
    );

    $array = $item->toArray();

    expect($array)->toBe([
        'type' => 'ip_address',
        'value' => '192.168.1.1',
        'resolvedCountryCode' => 'DE',
        'source' => 'geo_ip',
        'capturedAt' => $capturedAt->format('c'),
    ]);
});
