<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\LaravelTaxEngineServiceProvider;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Services\RulesEngineService;

it('merges config', function () {
    $config = $this->app['config']->get('tax');

    expect($config)->not->toBeNull()
        ->and($config)->toHaveKeys([
            'seller', 'oss', 'ioss', 'thresholds', 'rounding',
            'rules', 'vat_validation', 'currency', 'compliance',
        ]);
});

it('has correct config defaults', function () {
    expect(config('tax.seller.country'))->toBe('NL')
        ->and(config('tax.oss.enabled'))->toBeFalse()
        ->and(config('tax.rounding.mode'))->toBe('half_up')
        ->and(config('tax.currency'))->toBe('EUR');
});

it('resolves RateRepositoryContract to StaticRateRepository', function () {
    $repository = $this->app->make(RateRepositoryContract::class);

    expect($repository)->toBeInstanceOf(StaticRateRepository::class);
});

it('resolves RulesEngineService with all rules registered', function () {
    $engine = $this->app->make(RulesEngineService::class);

    expect($engine)->toBeInstanceOf(RulesEngineService::class)
        ->and($engine->rules())->toHaveCount(8);
});

it('resolves CalculateTaxAction', function () {
    $action = $this->app->make(CalculateTaxAction::class);

    expect($action)->toBeInstanceOf(CalculateTaxAction::class);
});

it('has publishable config', function () {
    $paths = LaravelTaxEngineServiceProvider::pathsToPublish(
        LaravelTaxEngineServiceProvider::class,
        'tax-engine-config'
    );

    expect($paths)->not->toBeEmpty();

    $sourcePath = array_key_first($paths);
    expect($sourcePath)->toContain('config/tax.php');
});
