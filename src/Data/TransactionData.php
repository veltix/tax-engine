<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

final readonly class TransactionData
{
    public function __construct(
        public string $transactionId,
        /** The transaction amount, assumed to be tax-exclusive (net). */
        public Money $amount,
        public Country $sellerCountry,
        public Country $buyerCountry,
        public CustomerType $customerType,
        public SupplyType $supplyType,
        public ?string $buyerVatNumber = null,
        public ?string $sellerVatNumber = null,
        public ?DateTimeImmutable $date = null,
        public ?string $description = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            transactionId: $data['transactionId'],
            amount: $data['amount'] instanceof Money
                ? $data['amount']
                : Money::fromCents($data['amount']),
            sellerCountry: $data['sellerCountry'] instanceof Country
                ? $data['sellerCountry']
                : new Country($data['sellerCountry']),
            buyerCountry: $data['buyerCountry'] instanceof Country
                ? $data['buyerCountry']
                : new Country($data['buyerCountry']),
            customerType: $data['customerType'] instanceof CustomerType
                ? $data['customerType']
                : CustomerType::from($data['customerType']),
            supplyType: $data['supplyType'] instanceof SupplyType
                ? $data['supplyType']
                : SupplyType::from($data['supplyType']),
            buyerVatNumber: $data['buyerVatNumber'] ?? null,
            sellerVatNumber: $data['sellerVatNumber'] ?? null,
            date: isset($data['date'])
                ? ($data['date'] instanceof DateTimeImmutable
                    ? $data['date']
                    : new DateTimeImmutable($data['date']))
                : null,
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function isB2B(): bool
    {
        return $this->customerType === CustomerType::B2B;
    }

    public function isB2C(): bool
    {
        return $this->customerType === CustomerType::B2C;
    }

    public function isDomestic(): bool
    {
        return $this->sellerCountry->isDomesticTo($this->buyerCountry);
    }

    public function isCrossBorderEu(): bool
    {
        return $this->sellerCountry->isCrossBorderEu($this->buyerCountry);
    }

    public function isExport(): bool
    {
        return $this->sellerCountry->isEu() && $this->buyerCountry->isOutsideEu();
    }

    public function isDigitalService(): bool
    {
        return in_array($this->supplyType, [
            SupplyType::DigitalServices,
            SupplyType::Telecommunications,
            SupplyType::Broadcasting,
        ], true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'amount' => $this->amount->toArray(),
            'sellerCountry' => $this->sellerCountry->code,
            'buyerCountry' => $this->buyerCountry->code,
            'customerType' => $this->customerType->value,
            'supplyType' => $this->supplyType->value,
            'buyerVatNumber' => $this->buyerVatNumber,
            'sellerVatNumber' => $this->sellerVatNumber,
            'date' => $this->date?->format('c'),
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
