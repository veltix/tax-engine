<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

final class VersionInfo
{
    public static function ruleVersion(): string
    {
        return '1.0.0';
    }

    public static function rateDatasetVersion(): string
    {
        return EuVatRates::version();
    }

    public static function policyHash(): string
    {
        $components = [
            'rule_version' => self::ruleVersion(),
            'rate_version' => self::rateDatasetVersion(),
        ];

        return hash('sha256', json_encode($components, JSON_THROW_ON_ERROR));
    }
}
