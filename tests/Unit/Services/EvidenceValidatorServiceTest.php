<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Exceptions\EvidenceConflictException;
use Veltix\TaxEngine\Exceptions\InsufficientEvidenceException;
use Veltix\TaxEngine\Services\EvidenceValidatorService;

beforeEach(function () {
    $this->service = new EvidenceValidatorService();
});

it('throws InsufficientEvidenceException in strict mode with fewer than 2 signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $this->service->validate($evidence, 'strict');
})->throws(InsufficientEvidenceException::class);

it('throws EvidenceConflictException in strict mode when signals conflict', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $this->service->validate($evidence, 'strict');
})->throws(EvidenceConflictException::class);

it('validates successfully with 2+ matching signals in strict mode', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
    );

    $result = $this->service->validate($evidence, 'strict');

    expect($result->valid)->toBeTrue()
        ->and($result->resolvedCountry->code)->toBe('DE')
        ->and($result->mode)->toBe('strict')
        ->and($result->failureReason)->toBeNull();
});

it('flags issues but passes in tolerant mode with insufficient signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $result = $this->service->validate($evidence, 'tolerant');

    expect($result->valid)->toBeFalse()
        ->and($result->resolvedCountry->code)->toBe('DE')
        ->and($result->mode)->toBe('tolerant')
        ->and($result->failureReason)->not->toBeNull();
});

it('flags issues but passes in tolerant mode with conflicting signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $result = $this->service->validate($evidence, 'tolerant');

    expect($result->valid)->toBeFalse()
        ->and($result->mode)->toBe('tolerant')
        ->and($result->failureReason)->toContain('Conflicting')
        ->and($result->conflictingEvidence->count())->toBeGreaterThan(0);
});

it('resolves location with high confidence for 3+ matching signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
        EvidenceItemData::bankCountry('DE'),
    );

    $location = $this->service->resolveLocation($evidence, 'strict');

    expect($location->resolvedCountry->code)->toBe('DE')
        ->and($location->confidenceLevel)->toBe('high')
        ->and($location->requiresManualReview)->toBeFalse();
});

it('resolves location with medium confidence for 2 matching signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
    );

    $location = $this->service->resolveLocation($evidence, 'strict');

    expect($location->confidenceLevel)->toBe('medium')
        ->and($location->requiresManualReview)->toBeFalse();
});

it('resolves location with manual review flag in tolerant mode on conflict', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $location = $this->service->resolveLocation($evidence, 'tolerant');

    expect($location->requiresManualReview)->toBeTrue()
        ->and($location->evidenceIgnored->count())->toBeGreaterThan(0);
});

it('determines majority country correctly', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
        EvidenceItemData::bankCountry('FR'),
    );

    $location = $this->service->resolveLocation($evidence, 'tolerant');

    expect($location->resolvedCountry->code)->toBe('DE');
});
