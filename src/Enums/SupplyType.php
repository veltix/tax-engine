<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum SupplyType: string
{
    case Goods = 'goods';
    case Services = 'services';
    case DigitalServices = 'digital_services';
    case Telecommunications = 'telecom';
    case Broadcasting = 'broadcasting';
}
