<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use Veltix\TaxEngine\Enums\EvidenceSource;
use Veltix\TaxEngine\Enums\EvidenceType;

final readonly class EvidenceItemData
{
    public function __construct(
        public EvidenceType $type,
        public mixed $value,
        public string $resolvedCountryCode,
        public EvidenceSource $source,
        public DateTimeImmutable $capturedAt,
    ) {
        if (! preg_match('/^[A-Z]{2}$/', $this->resolvedCountryCode)) {
            throw new InvalidArgumentException(
                "Invalid ISO 3166-1 alpha-2 country code: {$this->resolvedCountryCode}"
            );
        }
    }

    public static function billingAddress(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::BillingAddress,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::BillingSystem,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function ipAddress(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::IpAddress,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::GeoIp,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function bankCountry(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::BankCountry,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::PaymentProvider,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function simCountry(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::SimCountry,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::PhoneNumber,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function paymentProviderCountry(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::PaymentProviderCountry,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::PaymentProvider,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function shippingAddress(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::ShippingAddress,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::BillingSystem,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    public static function selfDeclaredCountry(string $countryCode, mixed $value = null, ?DateTimeImmutable $capturedAt = null): self
    {
        return new self(
            type: EvidenceType::SelfDeclaredCountry,
            value: $value ?? $countryCode,
            resolvedCountryCode: strtoupper($countryCode),
            source: EvidenceSource::UserDeclaration,
            capturedAt: $capturedAt ?? new DateTimeImmutable(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value,
            'resolvedCountryCode' => $this->resolvedCountryCode,
            'source' => $this->source->value,
            'capturedAt' => $this->capturedAt->format('c'),
        ];
    }
}
