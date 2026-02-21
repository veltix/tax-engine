<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Enums\EvidenceSource;
use Veltix\TaxEngine\Enums\EvidenceType;

final class DatabaseEvidenceStorage implements EvidenceStorageContract
{
    public function store(string $snapshotId, EvidenceData $evidence): void
    {
        DB::table('tax_evidence')
            ->where('decision_id', $snapshotId)
            ->delete();

        foreach ($evidence->items as $item) {
            DB::table('tax_evidence')->insert([
                'decision_id' => $snapshotId,
                'evidence_type' => $item->type->value,
                'evidence_value' => is_string($item->value) ? $item->value : json_encode($item->value),
                'country_code' => $item->resolvedCountryCode,
                'source' => $item->source->value,
                'captured_at' => $item->capturedAt->format('Y-m-d H:i:s'),
                'created_at' => now()->toDateTimeString(),
            ]);
        }
    }

    public function findBySnapshotId(string $snapshotId): ?EvidenceData
    {
        $rows = DB::table('tax_evidence')
            ->where('decision_id', $snapshotId)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $items = $rows->map(fn (object $row) => new EvidenceItemData(
            type: EvidenceType::from($row->evidence_type),
            value: $row->evidence_value,
            resolvedCountryCode: $row->country_code,
            source: EvidenceSource::from($row->source),
            capturedAt: new DateTimeImmutable($row->captured_at),
        ))->all();

        return new EvidenceData($items);
    }
}
