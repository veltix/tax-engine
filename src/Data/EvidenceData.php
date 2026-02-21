<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Enums\EvidenceType;

final readonly class EvidenceData
{
    /** @param EvidenceItemData[] $items */
    public function __construct(
        public array $items = [],
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    public static function fromItems(EvidenceItemData ...$items): self
    {
        return new self(array_values($items));
    }

    public function add(EvidenceItemData $item): self
    {
        return new self([...$this->items, $item]);
    }

    public function merge(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    /** @return EvidenceItemData[] */
    public function ofType(EvidenceType $type): array
    {
        return array_values(
            array_filter($this->items, fn (EvidenceItemData $item) => $item->type === $type)
        );
    }

    public function findByType(EvidenceType $type): ?EvidenceItemData
    {
        foreach ($this->items as $item) {
            if ($item->type === $type) {
                return $item;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return string[] Unique resolved country codes */
    public function countrySignals(): array
    {
        return array_values(array_unique(
            array_map(fn (EvidenceItemData $item) => $item->resolvedCountryCode, $this->items)
        ));
    }

    public function matchingCountryCount(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        $counts = array_count_values(
            array_map(fn (EvidenceItemData $item) => $item->resolvedCountryCode, $this->items)
        );

        return max($counts);
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return array_values(array_map(fn (EvidenceItemData $item) => $item->toArray(), $this->items));
    }
}
