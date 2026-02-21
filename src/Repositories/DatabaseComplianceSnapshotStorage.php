<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\ResolvedLocationData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\EvidenceSource;
use Veltix\TaxEngine\Enums\EvidenceType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;

final class DatabaseComplianceSnapshotStorage implements ComplianceSnapshotStorageContract
{
    public function store(ComplianceSnapshotData $snapshot): void
    {
        DB::table('tax_decisions')->insert([
            'id' => $snapshot->snapshotId,
            'order_reference' => $snapshot->transactionId,
            'seller_country' => $snapshot->transaction->sellerCountry->code,
            'buyer_country' => $snapshot->transaction->buyerCountry->code,
            'resolved_country' => $snapshot->resolvedLocation?->resolvedCountry->code
                ?? $snapshot->transaction->buyerCountry->code,
            'tax_scheme' => $snapshot->decision->scheme->value,
            'rule_version' => $snapshot->ruleVersion,
            'rate_version' => $snapshot->rateDatasetVersion,
            'snapshot_data' => json_encode($snapshot->toArray()),
            'created_at' => $snapshot->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findBySnapshotId(string $snapshotId): ?ComplianceSnapshotData
    {
        $row = DB::table('tax_decisions')->where('id', $snapshotId)->first();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByTransactionId(string $transactionId): ?ComplianceSnapshotData
    {
        $row = DB::table('tax_decisions')
            ->where('order_reference', $transactionId)
            ->latest('created_at')
            ->first();

        return $row ? $this->hydrate($row) : null;
    }

    /** @return ComplianceSnapshotData[] */
    public function all(): array
    {
        return DB::table('tax_decisions')
            ->orderBy('created_at')
            ->get()
            ->map(fn (object $row) => $this->hydrate($row))
            ->all();
    }

    private function hydrate(object $row): ComplianceSnapshotData
    {
        /** @var string $snapshotJson */
        $snapshotJson = $row->snapshot_data; // @phpstan-ignore-line
        $data = json_decode($snapshotJson, true);

        $txnData = $data['transaction'];
        if (is_array($txnData['amount'] ?? null)) {
            $txnData['amount'] = $txnData['amount']['amount'];
        }
        $transaction = TransactionData::from($txnData);

        $decision = new TaxDecisionData(
            scheme: TaxScheme::from($data['decision']['scheme']),
            rate: $data['decision']['rate'],
            taxCountry: new Country($data['decision']['taxCountry']),
            ruleApplied: $data['decision']['ruleApplied'],
            reasoning: $data['decision']['reasoning'],
            vatNumberValidated: $data['decision']['vatNumberValidated'],
            reverseCharged: $data['decision']['reverseCharged'],
            decidedAt: isset($data['decision']['decidedAt'])
                ? new DateTimeImmutable($data['decision']['decidedAt'])
                : null,
            evidence: $data['decision']['evidence'] ?? [],
        );

        $evidence = $this->hydrateEvidence($data['evidence'] ?? []);

        $resolvedLocation = isset($data['resolvedLocation'])
            ? new ResolvedLocationData(
                resolvedCountry: new Country($data['resolvedLocation']['resolvedCountry']),
                evidenceUsed: $this->hydrateEvidence($data['resolvedLocation']['evidenceUsed'] ?? []),
                evidenceIgnored: $this->hydrateEvidence($data['resolvedLocation']['evidenceIgnored'] ?? []),
                confidenceLevel: $data['resolvedLocation']['confidenceLevel'],
                requiresManualReview: $data['resolvedLocation']['requiresManualReview'],
                summary: $data['resolvedLocation']['summary'] ?? null,
            )
            : null;

        return new ComplianceSnapshotData(
            snapshotId: $data['snapshotId'],
            transactionId: $data['transactionId'],
            transaction: $transaction,
            decision: $decision,
            evidence: $evidence,
            resolvedLocation: $resolvedLocation,
            ruleVersion: $data['ruleVersion'] ?? null,
            rateDatasetVersion: $data['rateDatasetVersion'] ?? null,
            policyHash: $data['policyHash'] ?? null,
            createdAt: new DateTimeImmutable($data['createdAt']),
            metadata: $data['metadata'] ?? [],
        );
    }

    /** @param list<array{type: string, value: mixed, resolvedCountryCode: string, source: string, capturedAt: string}> $items */
    private function hydrateEvidence(array $items): EvidenceData
    {
        $evidenceItems = array_map(
            fn (array $item) => new EvidenceItemData(
                type: EvidenceType::from($item['type']),
                value: $item['value'],
                resolvedCountryCode: $item['resolvedCountryCode'],
                source: EvidenceSource::from($item['source']),
                capturedAt: new DateTimeImmutable($item['capturedAt']),
            ),
            $items,
        );

        return new EvidenceData($evidenceItems);
    }
}
