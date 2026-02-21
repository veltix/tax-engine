<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum EvidenceSource: string
{
    case Transaction = 'transaction';
    case GeoIp = 'geo_ip';
    case PaymentProvider = 'payment_provider';
    case BillingSystem = 'billing_system';
    case PhoneNumber = 'phone_number';
    case UserDeclaration = 'user_declaration';
    case VatValidation = 'vat_validation';
    case Manual = 'manual';
}
