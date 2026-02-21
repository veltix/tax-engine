<?php

return [

    'seller' => [
        'country' => env('TAX_SELLER_COUNTRY', 'NL'),
        'vat_number' => env('TAX_SELLER_VAT_NUMBER'),
    ],

    'oss' => [
        'enabled' => env('TAX_OSS_ENABLED', false),
        'registration_country' => env('TAX_OSS_REGISTRATION_COUNTRY'),
    ],

    'ioss' => [
        'enabled' => env('TAX_IOSS_ENABLED', false),
        'number' => env('TAX_IOSS_NUMBER'),
        'excluded_categories' => [],
    ],

    'thresholds' => [
        'oss_micro_business' => (int) env('TAX_OSS_MICRO_BUSINESS_THRESHOLD', 1000000),
        'ioss_consignment_max' => (int) env('TAX_IOSS_CONSIGNMENT_MAX_CENTS', 15000),
    ],

    'price_mode' => env('TAX_PRICE_MODE', 'tax_exclusive'),

    'rounding' => [
        'mode' => env('TAX_ROUNDING_MODE', 'half_up'),
        'strategy' => env('TAX_ROUNDING_STRATEGY', 'per_line'),
    ],

    'rules' => [
        'domestic_standard' => true,
        'reverse_charge' => true,
        'oss' => true,
        'export' => true,
        'domestic_reverse_charge' => true,
        'ioss' => true,
        'cross_border_b2c_fallback' => true,
        'service_export' => true,
    ],

    'vat_validation' => [
        'driver' => env('TAX_VAT_VALIDATION_DRIVER', 'vies'),
        'cache_ttl' => (int) env('TAX_VAT_CACHE_TTL', 3600),
        'timeout' => (int) env('TAX_VAT_TIMEOUT', 10),
    ],

    'currency' => env('TAX_DEFAULT_CURRENCY', 'EUR'),

    'compliance' => [
        'store_decisions' => env('TAX_STORE_DECISIONS', true),
        'store_evidence' => env('TAX_STORE_EVIDENCE', true),
        // Note: when using database driver, store_evidence requires store_decisions (FK constraint)
        'storage_driver' => env('TAX_COMPLIANCE_STORAGE', 'array'),
        'retention_years' => (int) env('TAX_RETENTION_YEARS', 10),
        'async' => env('TAX_COMPLIANCE_ASYNC', false),
    ],

];
