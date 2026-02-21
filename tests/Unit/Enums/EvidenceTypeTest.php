<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\EvidenceType;

it('has 7 cases', function () {
    expect(EvidenceType::cases())->toHaveCount(7);
});

it('has string backing values', function (EvidenceType $type, string $expected) {
    expect($type->value)->toBe($expected);
})->with([
    [EvidenceType::BillingAddress, 'billing_address'],
    [EvidenceType::IpAddress, 'ip_address'],
    [EvidenceType::BankCountry, 'bank_country'],
    [EvidenceType::SimCountry, 'sim_country'],
    [EvidenceType::PaymentProviderCountry, 'payment_provider_country'],
    [EvidenceType::ShippingAddress, 'shipping_address'],
    [EvidenceType::SelfDeclaredCountry, 'self_declared_country'],
]);

it('can be created from string value', function () {
    expect(EvidenceType::from('billing_address'))->toBe(EvidenceType::BillingAddress);
    expect(EvidenceType::from('ip_address'))->toBe(EvidenceType::IpAddress);
});
