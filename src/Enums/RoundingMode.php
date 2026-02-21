<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum RoundingMode: string
{
    case HalfUp = 'half_up';
    case HalfDown = 'half_down';
    case HalfEven = 'half_even';
}
