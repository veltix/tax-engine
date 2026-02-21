<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\EvidenceType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->service = new EvidenceCollectorService();
    $this->transaction = new TransactionData(
        transactionId: 'txn-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );
});

it('collects evidence from transaction', function () {
    $evidence = $this->service->collect($this->transaction);

    expect($evidence->count())->toBeGreaterThanOrEqual(1)
        ->and($evidence->findByType(EvidenceType::BillingAddress))->not->toBeNull()
        ->and($evidence->findByType(EvidenceType::BillingAddress)->resolvedCountryCode)->toBe('DE');
});

it('collects evidence with VAT validation result', function () {
    $vatResult = VatValidationResultData::validResult('DE', '123456789');

    $evidence = $this->service->collect($this->transaction, $vatResult);

    expect($evidence->count())->toBeGreaterThanOrEqual(2);
});

it('does not add VAT evidence for invalid VAT result', function () {
    $vatResult = VatValidationResultData::invalid('DE', '123456789', 'Invalid');

    $evidence = $this->service->collect($this->transaction, $vatResult);

    // Only transaction evidence, no VAT evidence
    expect($evidence->count())->toBe(1);
});

it('collects evidence with additional signals', function () {
    $evidence = $this->service->collect($this->transaction, null, [
        'ip_country' => 'DE',
        'bank_country' => 'DE',
    ]);

    expect($evidence->count())->toBe(3)
        ->and($evidence->findByType(EvidenceType::IpAddress))->not->toBeNull()
        ->and($evidence->findByType(EvidenceType::BankCountry))->not->toBeNull();
});

it('extracts evidence from transaction only via fromTransaction', function () {
    $evidence = $this->service->fromTransaction($this->transaction);

    expect($evidence->count())->toBe(1)
        ->and($evidence->findByType(EvidenceType::BillingAddress)->resolvedCountryCode)->toBe('DE');
});

it('extracts evidence from VAT validation via fromVatValidation', function () {
    $vatResult = VatValidationResultData::validResult('DE', '123456789');

    $evidence = $this->service->fromVatValidation($vatResult);

    expect($evidence->count())->toBe(1);
});

it('returns empty evidence from invalid VAT validation', function () {
    $vatResult = VatValidationResultData::invalid('DE', '123456789', 'Invalid');

    $evidence = $this->service->fromVatValidation($vatResult);

    expect($evidence->isEmpty())->toBeTrue();
});
