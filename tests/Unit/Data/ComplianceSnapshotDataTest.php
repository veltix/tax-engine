<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->snapshotTransaction = new TransactionData(
        transactionId: 'txn-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $this->snapshotDecision = new TaxDecisionData(
        scheme: TaxScheme::OSS,
        rate: '19.00',
        taxCountry: new Country('DE'),
        ruleApplied: 'oss',
        reasoning: 'Cross-border B2C digital service',
    );
});

it('creates a snapshot with generated ID and timestamp', function () {
    $evidence = EvidenceData::fromItems(EvidenceItemData::billingAddress('DE'));

    $snapshot = ComplianceSnapshotData::create(
        transaction: $this->snapshotTransaction,
        decision: $this->snapshotDecision,
        evidence: $evidence,
    );

    expect($snapshot->snapshotId)->toBeString()->not->toBeEmpty()
        ->and($snapshot->transactionId)->toBe('txn-001')
        ->and($snapshot->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and(strlen($snapshot->snapshotId))->toBe(32);
});

it('includes metadata when provided', function () {
    $snapshot = ComplianceSnapshotData::create(
        transaction: $this->snapshotTransaction,
        decision: $this->snapshotDecision,
        evidence: EvidenceData::empty(),
        metadata: ['source' => 'api'],
    );

    expect($snapshot->metadata)->toBe(['source' => 'api']);
});

it('converts to array with all nested DTOs', function () {
    $snapshot = ComplianceSnapshotData::create(
        transaction: $this->snapshotTransaction,
        decision: $this->snapshotDecision,
        evidence: EvidenceData::fromItems(EvidenceItemData::billingAddress('DE')),
        ruleVersion: '1.0',
        rateDatasetVersion: '2024-01',
    );

    $array = $snapshot->toArray();

    expect($array)->toHaveKeys([
        'snapshotId', 'transactionId', 'transaction', 'decision',
        'evidence', 'resolvedLocation', 'ruleVersion', 'rateDatasetVersion',
        'createdAt', 'metadata',
    ])
        ->and($array['transaction'])->toBeArray()
        ->and($array['decision'])->toBeArray()
        ->and($array['evidence'])->toBeArray()
        ->and($array['ruleVersion'])->toBe('1.0')
        ->and($array['rateDatasetVersion'])->toBe('2024-01');
});
