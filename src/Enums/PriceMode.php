<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum PriceMode: string
{
    case TaxExclusive = 'tax_exclusive';
    case TaxInclusive = 'tax_inclusive';
}
