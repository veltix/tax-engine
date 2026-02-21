<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;

final readonly class VatValidationResultData
{
    public function __construct(
        public bool $valid,
        public string $countryCode,
        public string $vatNumber,
        public ?string $name = null,
        public ?string $address = null,
        public ?DateTimeImmutable $requestDate = null,
        public bool $formatValid = true,
        public ?string $failureReason = null,
    ) {}

    public static function invalid(
        string $countryCode,
        string $vatNumber,
        string $reason,
        bool $formatValid = true,
    ): self {
        return new self(
            valid: false,
            countryCode: $countryCode,
            vatNumber: $vatNumber,
            requestDate: new DateTimeImmutable(),
            formatValid: $formatValid,
            failureReason: $reason,
        );
    }

    public static function validResult(
        string $countryCode,
        string $vatNumber,
        ?string $name = null,
        ?string $address = null,
    ): self {
        return new self(
            valid: true,
            countryCode: $countryCode,
            vatNumber: $vatNumber,
            name: $name,
            address: $address,
            requestDate: new DateTimeImmutable(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'countryCode' => $this->countryCode,
            'vatNumber' => $this->vatNumber,
            'name' => $this->name,
            'address' => $this->address,
            'requestDate' => $this->requestDate?->format('c'),
            'formatValid' => $this->formatValid,
            'failureReason' => $this->failureReason,
        ];
    }
}
