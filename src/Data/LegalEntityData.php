<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Country;

final readonly class LegalEntityData
{
    public function __construct(
        public Country $country,
        public ?string $vatNumber = null,
        public bool $ossRegistered = false,
        public bool $iossRegistered = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'country' => $this->country->code,
            'vatNumber' => $this->vatNumber,
            'ossRegistered' => $this->ossRegistered,
            'iossRegistered' => $this->iossRegistered,
        ];
    }
}
