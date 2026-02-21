<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum TaxScheme: string
{
    case Standard = 'standard';
    case ReverseCharge = 'reverse_charge';
    case OSS = 'oss';
    case Export = 'export';
    case Exempt = 'exempt';
    case DomesticReverseCharge = 'domestic_reverse_charge';
    case IOSS = 'ioss';
    case OutsideScope = 'outside_scope';
}
