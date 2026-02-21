<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Listeners;

use Veltix\TaxEngine\Actions\StoreComplianceSnapshotAction;
use Veltix\TaxEngine\Events\TaxCalculated;

class StoreComplianceSnapshotListener
{
    public function __construct(
        private readonly StoreComplianceSnapshotAction $action,
    ) {}

    public function handle(TaxCalculated $event): void
    {
        $this->action->execute(
            transaction: $event->transaction,
            decision: $event->result->decision,
            vatResult: $event->vatResult,
            metadata: $event->metadata,
        );
    }
}
