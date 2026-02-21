<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Policies;

use Veltix\TaxEngine\Contracts\RoundingPolicyContract;
use Veltix\TaxEngine\Enums\RoundingMode;
use Veltix\TaxEngine\Enums\RoundingStrategy;
use Veltix\TaxEngine\Support\Money;

final class DefaultRoundingPolicy implements RoundingPolicyContract
{
    public function __construct(
        private readonly RoundingStrategy $strategy = RoundingStrategy::PerLine,
    ) {}

    public function round(Money $amount, RoundingMode $mode): Money
    {
        return $amount;
    }

    public function roundingStrategy(): RoundingStrategy
    {
        return $this->strategy;
    }
}
