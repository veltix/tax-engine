<?php

declare(strict_types=1);

use Illuminate\Contracts\Events\Dispatcher;
use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Events\TaxCalculated;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Rules\ServiceExportRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function buildCalculateAction(?Dispatcher $events = null): CalculateTaxAction
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ExportRule());
    $engine->addRule(new ServiceExportRule());
    $engine->addRule(new ReverseChargeRule());
    $engine->addRule(new DomesticStandardRule($rates));

    return new CalculateTaxAction(
        rulesEngine: $engine,
        events: $events,
    );
}

it('can be resolved from the container', function () {
    $action = app(CalculateTaxAction::class);

    expect($action)->toBeInstanceOf(CalculateTaxAction::class);
});

it('calculates domestic standard VAT', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-calc-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = $action->execute($transaction);

    expect($result)->toBeInstanceOf(TaxResultData::class)
        ->and($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->grossAmount->amount)->toBe(11900)
        ->and($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('19.00')
        ->and($result->transactionId)->toBe('txn-calc-001');
});

it('calculates zero-rated export', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-calc-002',
        amount: Money::fromCents(50000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    $result = $action->execute($transaction);

    expect($result->taxAmount->amount)->toBe(0)
        ->and($result->grossAmount->amount)->toBe(50000)
        ->and($result->decision->scheme)->toBe(TaxScheme::OutsideScope)
        ->and($result->decision->isZeroRated())->toBeTrue();
});

it('calculates reverse charge', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-calc-003',
        amount: Money::fromCents(25000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    );

    $vatResult = VatValidationResultData::validResult('FR', 'FR12345678901');

    $result = $action->execute($transaction, vatResult: $vatResult);

    expect($result->taxAmount->amount)->toBe(0)
        ->and($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->reverseCharged)->toBeTrue();
});

it('dispatches TaxCalculated event for compliance storage', function () {
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(TaxCalculated::class));

    $action = buildCalculateAction(events: $dispatcher);

    $transaction = new TransactionData(
        transactionId: 'txn-compliance-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $action->execute($transaction);
});

it('works without event dispatcher', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-no-compliance',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = $action->execute($transaction);

    expect($result->decision->scheme)->toBe(TaxScheme::Standard);
});

it('calculates correct effective rate', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-eff-rate',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = $action->execute($transaction);

    expect($result->effectiveRate())->toBe('19.00');
});

it('handles different country rates', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'txn-hu',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('HU'),
        buyerCountry: new Country('HU'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = $action->execute($transaction);

    expect($result->taxAmount->amount)->toBe(2700)
        ->and($result->decision->rate)->toBe('27.00')
        ->and($result->decision->taxCountry->code)->toBe('HU');
});

it('preserves transaction ID in result', function () {
    $action = buildCalculateAction();

    $transaction = new TransactionData(
        transactionId: 'my-unique-txn-id',
        amount: Money::fromCents(5000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Services,
    );

    $result = $action->execute($transaction);

    expect($result->transactionId)->toBe('my-unique-txn-id');
});
