<?php

declare(strict_types=1);

use Veltix\TaxEngine\Tests\TestCase;

uses(TestCase::class)->in(
    'Unit/LaravelTaxEngineServiceProviderTest.php',
    'Unit/Services/ViesVatValidatorTest.php',
    'Unit/Services/CachingVatValidatorTest.php',
    'Unit/Services/VatValidatorServiceTest.php',
    'Unit/Actions/ValidateVatNumberActionTest.php',
    'Unit/Rules/ValidVatNumberTest.php',
    'Unit/Actions/StoreComplianceSnapshotActionTest.php',
    'Unit/Actions/CalculateTaxActionTest.php',
    'Unit/Actions/CalculateInvoiceTaxActionTest.php',
    'Feature',
);
