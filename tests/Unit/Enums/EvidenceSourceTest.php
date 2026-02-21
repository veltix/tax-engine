<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\EvidenceSource;

it('has 8 cases', function () {
    expect(EvidenceSource::cases())->toHaveCount(8);
});

it('has string backing values', function (EvidenceSource $source, string $expected) {
    expect($source->value)->toBe($expected);
})->with([
    [EvidenceSource::Transaction, 'transaction'],
    [EvidenceSource::GeoIp, 'geo_ip'],
    [EvidenceSource::PaymentProvider, 'payment_provider'],
    [EvidenceSource::BillingSystem, 'billing_system'],
    [EvidenceSource::PhoneNumber, 'phone_number'],
    [EvidenceSource::UserDeclaration, 'user_declaration'],
    [EvidenceSource::VatValidation, 'vat_validation'],
    [EvidenceSource::Manual, 'manual'],
]);

it('can be created from string value', function () {
    expect(EvidenceSource::from('transaction'))->toBe(EvidenceSource::Transaction);
    expect(EvidenceSource::from('geo_ip'))->toBe(EvidenceSource::GeoIp);
});
