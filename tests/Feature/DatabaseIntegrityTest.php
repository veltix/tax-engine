<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Veltix\TaxEngine\Actions\StoreComplianceSnapshotAction;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\DatabaseComplianceSnapshotStorage;
use Veltix\TaxEngine\Repositories\DatabaseEvidenceStorage;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Enable FK constraints for SQLite
    config()->set('database.connections.testing.foreign_key_constraints', true);
    DB::purge('testing');
    DB::reconnect('testing');
    // Re-run migrations since we purged the connection
    $this->artisan('migrate:fresh');
});

it('rejects evidence with non-existent decision_id (FK constraint)', function () {
    $evidenceStorage = new DatabaseEvidenceStorage();
    $evidence = EvidenceData::fromItems(EvidenceItemData::billingAddress('DE'));

    $evidenceStorage->store('non-existent-id', $evidence);
})->throws(\Illuminate\Database\QueryException::class);

it('cascade deletes evidence when tax_decisions row is deleted', function () {
    $snapshotStorage = new DatabaseComplianceSnapshotStorage();
    $evidenceStorage = new DatabaseEvidenceStorage();

    // Insert a parent snapshot
    DB::table('tax_decisions')->insert([
        'id' => 'cascade-test-001',
        'order_reference' => 'cascade-txn-001',
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
    );

    $evidenceStorage->store('cascade-test-001', $evidence);

    expect(DB::table('tax_evidence')->where('decision_id', 'cascade-test-001')->count())->toBe(2);

    // Delete the parent
    DB::table('tax_decisions')->where('id', 'cascade-test-001')->delete();

    // Evidence should be cascade deleted
    expect(DB::table('tax_evidence')->where('decision_id', 'cascade-test-001')->count())->toBe(0);
});

it('stores same transactionId twice as two separate snapshots', function () {
    $snapshotStorage = new DatabaseComplianceSnapshotStorage();

    DB::table('tax_decisions')->insert([
        'id' => 'dup-snap-001',
        'order_reference' => 'same-txn',
        'seller_country' => 'NL',
        'buyer_country' => 'DE',
        'resolved_country' => 'DE',
        'tax_scheme' => 'standard',
        'snapshot_data' => '{}',
        'created_at' => now()->toDateTimeString(),
    ]);

    DB::table('tax_decisions')->insert([
        'id' => 'dup-snap-002',
        'order_reference' => 'same-txn',
        'seller_country' => 'NL',
        'buyer_country' => 'FR',
        'resolved_country' => 'FR',
        'tax_scheme' => 'oss',
        'snapshot_data' => '{}',
        'created_at' => now()->toDateTimeString(),
    ]);

    $count = DB::table('tax_decisions')->where('order_reference', 'same-txn')->count();

    expect($count)->toBe(2);
});

it('rolls back snapshot when evidence storage fails atomically', function () {
    $snapshotStorage = new DatabaseComplianceSnapshotStorage();

    // Create a mock evidence storage that throws
    $failingEvidenceStorage = new class implements EvidenceStorageContract {
        public function store(string $snapshotId, EvidenceData $evidence): void
        {
            throw new \RuntimeException('Evidence storage failed');
        }

        public function findBySnapshotId(string $snapshotId): ?EvidenceData
        {
            return null;
        }
    };

    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: $snapshotStorage,
        evidenceStorage: $failingEvidenceStorage,
        storeDecisions: true,
        storeEvidence: true,
    );

    $transaction = new TransactionData(
        transactionId: 'atomic-rollback-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = new TaxDecisionData(
        scheme: TaxScheme::Standard,
        rate: '19.00',
        taxCountry: new Country('DE'),
        ruleApplied: 'domestic_standard',
        reasoning: 'Test atomic rollback',
    );

    try {
        $action->execute($transaction, $decision);
    } catch (\RuntimeException) {
        // Expected
    }

    // Snapshot should have been rolled back
    expect(DB::table('tax_decisions')->where('order_reference', 'atomic-rollback-001')->count())->toBe(0);
});
