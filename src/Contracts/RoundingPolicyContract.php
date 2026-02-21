<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Enums\RoundingMode;
use Veltix\TaxEngine\Enums\RoundingStrategy;
use Veltix\TaxEngine\Support\Money;

interface RoundingPolicyContract
{
    public function round(Money $amount, RoundingMode $mode): Money;

    public function roundingStrategy(): RoundingStrategy;
}
