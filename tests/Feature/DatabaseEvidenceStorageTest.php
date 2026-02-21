<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Enums\EvidenceType;
use Veltix\TaxEngine\Repositories\DatabaseEvidenceStorage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repo = new DatabaseEvidenceStorage();
});

it('stores and retrieves evidence by transaction ID', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    // DatabaseEvidenceStorage uses decision_id column which references tax_decisions table
    // We need a parent row first
    \Illuminate\Support\Facades\DB::table('tax_decisions')->insert([
        'id' => 'db-ev-txn-001',
        'order_reference' => 'db-ev-txn-001',
        'seller_country' => 'NL',
        'buyer_country' => 'DE',
        'resolved_country' => 'DE',
        'tax_scheme' => 'standard',
        'snapshot_data' => '{}',
        'created_at' => now()->toDateTimeString(),
    ]);

    $this->repo->store('db-ev-txn-001', $evidence);

    $found = $this->repo->findBySnapshotId('db-ev-txn-001');

    expect($found)->not->toBeNull()
        ->and($found->items)->toHaveCount(2)
        ->and($found->items[0]->resolvedCountryCode)->toBe('DE')
        ->and($found->items[0]->type)->toBe(EvidenceType::BillingAddress)
        ->and($found->items[1]->resolvedCountryCode)->toBe('FR')
        ->and($found->items[1]->type)->toBe(EvidenceType::IpAddress);
});

it('returns null for unknown transaction ID', function () {
    expect($this->repo->findBySnapshotId('nonexistent'))->toBeNull();
});

it('overwrites evidence for same transaction ID', function () {
    \Illuminate\Support\Facades\DB::table('tax_decisions')->insert([
        'id' => 'db-ev-overwrite',
        'order_reference' => 'db-ev-overwrite',
        'seller_country' => 'NL',
        'buyer_country' => 'DE',
        'resolved_country' => 'DE',
        'tax_scheme' => 'standard',
        'snapshot_data' => '{}',
        'created_at' => now()->toDateTimeString(),
    ]);

    $evidence1 = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
    );

    $this->repo->store('db-ev-overwrite', $evidence1);

    $evidence2 = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('FR'),
    );

    $this->repo->store('db-ev-overwrite', $evidence2);

    $found = $this->repo->findBySnapshotId('db-ev-overwrite');

    expect($found->items)->toHaveCount(1)
        ->and($found->items[0]->resolvedCountryCode)->toBe('FR');
});

it('stores multiple evidence items with correct sources', function () {
    \Illuminate\Support\Facades\DB::table('tax_decisions')->insert([
        'id' => 'db-ev-multi',
        'order_reference' => 'db-ev-multi',
        'seller_country' => 'NL',
        'buyer_country' => 'DE',
        'resolved_country' => 'DE',
        'tax_scheme' => 'standard',
        'snapshot_data' => '{}',
        'created_at' => now()->toDateTimeString(),
    ]);

    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
        EvidenceItemData::bankCountry('DE'),
        EvidenceItemData::simCountry('DE'),
    );

    $this->repo->store('db-ev-multi', $evidence);

    $found = $this->repo->findBySnapshotId('db-ev-multi');

    expect($found->items)->toHaveCount(4)
        ->and($found->countrySignals())->toBe(['DE']);
});
