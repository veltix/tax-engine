<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum EvidenceType: string
{
    case BillingAddress = 'billing_address';
    case IpAddress = 'ip_address';
    case BankCountry = 'bank_country';
    case SimCountry = 'sim_country';
    case PaymentProviderCountry = 'payment_provider_country';
    case ShippingAddress = 'shipping_address';
    case SelfDeclaredCountry = 'self_declared_country';
}
