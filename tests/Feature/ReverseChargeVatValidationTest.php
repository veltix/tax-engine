<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\CrossBorderB2CFallbackRule;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function rcEngine(): RulesEngineService
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ReverseChargeRule());
    $engine->addRule(new CrossBorderB2CFallbackRule($rates));
    $engine->addRule(new DomesticStandardRule($rates));

    return $engine;
}

function b2bCrossBorderTransaction(): TransactionData
{
    return new TransactionData(
        transactionId: 'rc-vat-test',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    );
}

it('applies reverse charge when VAT is valid', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('FR', 'FR12345678901'),
    );

    $result = rcEngine()->calculate(b2bCrossBorderTransaction(), $context);

    expect($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->vatNumberValidated)->toBeTrue()
        ->and($result->decision->reverseCharged)->toBeTrue()
        ->and($result->taxAmount->amount)->toBe(0);
});

it('does not apply reverse charge when vatResult is null', function () {
    // B2B cross-border without valid VAT → no applicable rule (RC skipped, fallback is B2C only)
    expect(fn () => rcEngine()->calculate(b2bCrossBorderTransaction(), new TaxCalculationContext()))
        ->toThrow(\Veltix\TaxEngine\Exceptions\NoApplicableRuleException::class);
});

it('does not apply reverse charge when VAT format is invalid', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'INVALID', 'Invalid VAT format', formatValid: false),
    );

    expect(fn () => rcEngine()->calculate(b2bCrossBorderTransaction(), $context))
        ->toThrow(\Veltix\TaxEngine\Exceptions\NoApplicableRuleException::class);
});

it('applies reverse charge on VIES outage (fail-open)', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'FR12345678901', 'VIES service unavailable'),
    );

    $result = rcEngine()->calculate(b2bCrossBorderTransaction(), $context);

    expect($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->vatNumberValidated)->toBeFalse()
        ->and($result->decision->reverseCharged)->toBeTrue();
});

it('does not apply reverse charge when VAT is invalid (non-outage)', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'FR12345678901', 'VAT number not found in VIES'),
    );

    expect(fn () => rcEngine()->calculate(b2bCrossBorderTransaction(), $context))
        ->toThrow(\Veltix\TaxEngine\Exceptions\NoApplicableRuleException::class);
});

it('does not apply reverse charge for B2C even with valid VAT', function () {
    $transaction = new TransactionData(
        transactionId: 'rc-b2c-test',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    );

    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('FR', 'FR12345678901'),
    );

    $result = rcEngine()->calculate($transaction, $context);

    expect($result->decision->scheme)->not->toBe(TaxScheme::ReverseCharge);
});
