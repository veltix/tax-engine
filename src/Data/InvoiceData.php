<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Support\Country;

final readonly class InvoiceData
{
    /**
     * @param InvoiceLineData[]    $lines
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $invoiceId,
        public Country $sellerCountry,
        public Country $buyerCountry,
        public CustomerType $customerType,
        public array $lines,
        public ?string $buyerVatNumber = null,
        public ?string $sellerVatNumber = null,
        public ?DateTimeImmutable $date = null,
        public array $metadata = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        $lines = array_map(
            fn (array $line) => InvoiceLineData::from($line),
            $data['lines'],
        );

        return new self(
            invoiceId: $data['invoiceId'],
            sellerCountry: $data['sellerCountry'] instanceof Country
                ? $data['sellerCountry']
                : new Country($data['sellerCountry']),
            buyerCountry: $data['buyerCountry'] instanceof Country
                ? $data['buyerCountry']
                : new Country($data['buyerCountry']),
            customerType: $data['customerType'] instanceof CustomerType
                ? $data['customerType']
                : CustomerType::from($data['customerType']),
            lines: $lines,
            buyerVatNumber: $data['buyerVatNumber'] ?? null,
            sellerVatNumber: $data['sellerVatNumber'] ?? null,
            date: isset($data['date'])
                ? ($data['date'] instanceof DateTimeImmutable
                    ? $data['date']
                    : new DateTimeImmutable($data['date']))
                : null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toTransactionData(InvoiceLineData $line): TransactionData
    {
        return new TransactionData(
            transactionId: "{$this->invoiceId}:{$line->lineId}",
            amount: $line->amount,
            sellerCountry: $this->sellerCountry,
            buyerCountry: $this->buyerCountry,
            customerType: $this->customerType,
            supplyType: $line->supplyType,
            buyerVatNumber: $this->buyerVatNumber,
            sellerVatNumber: $this->sellerVatNumber,
            date: $this->date,
            description: $line->description,
            metadata: array_merge($this->metadata, $line->metadata),
        );
    }
}
