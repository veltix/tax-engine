<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Exceptions\EvidenceConflictException;
use Veltix\TaxEngine\Exceptions\InsufficientEvidenceException;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Services\EvidenceValidatorService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('collects and validates evidence with matching signals', function () {
    $collector = app(EvidenceCollectorService::class);
    $validator = app(EvidenceValidatorService::class);

    $transaction = new TransactionData(
        transactionId: 'evidence-match-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $evidence = $collector->collect($transaction, null, [
        'ip_country' => 'DE',
    ]);

    $location = $validator->resolveLocation($evidence, 'strict');

    expect($location->resolvedCountry->code)->toBe('DE')
        ->and($location->confidenceLevel)->toBe('medium')
        ->and($location->requiresManualReview)->toBeFalse()
        ->and($location->meetsEuEvidenceThreshold())->toBeTrue();
});

it('achieves high confidence with 3+ matching signals', function () {
    $collector = app(EvidenceCollectorService::class);
    $validator = app(EvidenceValidatorService::class);

    $transaction = new TransactionData(
        transactionId: 'evidence-high-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $evidence = $collector->collect($transaction, null, [
        'ip_country' => 'FR',
        'bank_country' => 'FR',
    ]);

    $location = $validator->resolveLocation($evidence, 'strict');

    expect($location->resolvedCountry->code)->toBe('FR')
        ->and($location->confidenceLevel)->toBe('high')
        ->and($location->meetsEuEvidenceThreshold())->toBeTrue();
});

it('throws InsufficientEvidenceException in strict mode with only 1 signal', function () {
    $validator = app(EvidenceValidatorService::class);

    $evidence = \Veltix\TaxEngine\Data\EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $validator->resolveLocation($evidence, 'strict');
})->throws(InsufficientEvidenceException::class);

it('throws EvidenceConflictException in strict mode with conflicting signals', function () {
    $validator = app(EvidenceValidatorService::class);

    $evidence = \Veltix\TaxEngine\Data\EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $validator->resolveLocation($evidence, 'strict');
})->throws(EvidenceConflictException::class);

it('resolves in tolerant mode with conflicting signals', function () {
    $validator = app(EvidenceValidatorService::class);

    $evidence = \Veltix\TaxEngine\Data\EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $location = $validator->resolveLocation($evidence, 'tolerant');

    expect($location->requiresManualReview)->toBeTrue()
        ->and($location->evidenceIgnored->items)->not->toBeEmpty();
});

it('resolves in tolerant mode with insufficient signals', function () {
    $validator = app(EvidenceValidatorService::class);

    $evidence = \Veltix\TaxEngine\Data\EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $location = $validator->resolveLocation($evidence, 'tolerant');

    expect($location->resolvedCountry->code)->toBe('DE')
        ->and($location->requiresManualReview)->toBeTrue()
        ->and($location->confidenceLevel)->toBe('low');
});

it('collects evidence from VAT validation result', function () {
    $collector = app(EvidenceCollectorService::class);

    $transaction = new TransactionData(
        transactionId: 'evidence-vat-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    $vatResult = VatValidationResultData::validResult('DE', '123456789', 'Test GmbH');

    $evidence = $collector->collect($transaction, $vatResult);

    expect($evidence->items)->toHaveCount(2)
        ->and($evidence->countrySignals())->toBe(['DE']);
});

it('does not add VAT evidence for invalid validation', function () {
    $collector = app(EvidenceCollectorService::class);

    $transaction = new TransactionData(
        transactionId: 'evidence-invalid-vat-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    $vatResult = VatValidationResultData::invalid('DE', '123', 'Invalid format');

    $evidence = $collector->collect($transaction, $vatResult);

    // Only billing address from transaction, no VAT evidence
    expect($evidence->items)->toHaveCount(1);
});
