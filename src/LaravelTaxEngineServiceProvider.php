<?php

declare(strict_types=1);

namespace Veltix\TaxEngine;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Actions\StoreComplianceSnapshotAction;
use Veltix\TaxEngine\Actions\ValidateVatNumberAction;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Enums\RoundingMode;
use Veltix\TaxEngine\Events\TaxCalculated;
use Veltix\TaxEngine\Listeners\StoreComplianceSnapshotListener;
use Veltix\TaxEngine\Repositories\DatabaseComplianceSnapshotStorage;
use Veltix\TaxEngine\Repositories\DatabaseEvidenceStorage;
use Veltix\TaxEngine\Repositories\InMemoryComplianceSnapshotStorage;
use Veltix\TaxEngine\Repositories\InMemoryEvidenceStorage;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\CrossBorderB2CFallbackRule;
use Veltix\TaxEngine\Rules\DomesticReverseChargeRule;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Rules\IossRule;
use Veltix\TaxEngine\Rules\OssRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Rules\ServiceExportRule;
use Veltix\TaxEngine\Services\CachingVatValidator;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Services\EvidenceValidatorService;
use Veltix\TaxEngine\Services\NullVatValidator;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Services\VatValidatorService;
use Veltix\TaxEngine\Services\ViesVatValidator;

class LaravelTaxEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tax.php',
            'tax'
        );

        $this->registerPolicies();

        $this->app->singleton(VatValidatorContract::class, function ($app) {
            $driver = match ($app['config']['tax.vat_validation.driver']) {
                'null', 'testing' => new NullVatValidator(),
                default => new ViesVatValidator(
                    timeout: (int) $app['config']['tax.vat_validation.timeout'],
                ),
            };

            return new CachingVatValidator(
                inner: $driver,
                cache: $app->make(CacheRepository::class),
                ttl: (int) $app['config']['tax.vat_validation.cache_ttl'],
            );
        });

        $this->app->singleton(VatValidatorService::class, function ($app) {
            return new VatValidatorService(
                validator: $app->make(VatValidatorContract::class),
            );
        });

        $this->app->bind(ValidateVatNumberAction::class, function ($app) {
            return new ValidateVatNumberAction(
                service: $app->make(VatValidatorService::class),
            );
        });

        $this->app->singleton(EvidenceStorageContract::class, function ($app) {
            return match ($app['config']['tax.compliance.storage_driver']) {
                'database' => new DatabaseEvidenceStorage(),
                default => new InMemoryEvidenceStorage(),
            };
        });

        $this->app->singleton(ComplianceSnapshotStorageContract::class, function ($app) {
            return match ($app['config']['tax.compliance.storage_driver']) {
                'database' => new DatabaseComplianceSnapshotStorage(),
                default => new InMemoryComplianceSnapshotStorage(),
            };
        });

        $this->app->singleton(RateRepositoryContract::class, function () {
            return new StaticRateRepository();
        });

        $this->registerRules();

        $this->app->singleton(RulesEngineService::class, function ($app) {
            $config = $app['config'];
            $roundingMode = RoundingMode::tryFrom($config['tax.rounding.mode']) ?? RoundingMode::HalfUp;

            $engine = new RulesEngineService($roundingMode);

            foreach ($app->tagged('tax-engine.rules') as $rule) {
                $engine->addRule($rule);
            }

            return $engine;
        });

        $this->app->singleton(EvidenceCollectorService::class, function () {
            return new EvidenceCollectorService();
        });

        $this->app->singleton(EvidenceValidatorService::class, function () {
            return new EvidenceValidatorService();
        });

        $this->app->bind(StoreComplianceSnapshotAction::class, function ($app) {
            return new StoreComplianceSnapshotAction(
                collector: $app->make(EvidenceCollectorService::class),
                snapshotStorage: $app->make(ComplianceSnapshotStorageContract::class),
                evidenceStorage: $app->make(EvidenceStorageContract::class),
                storeDecisions: (bool) $app['config']['tax.compliance.store_decisions'],
                storeEvidence: (bool) $app['config']['tax.compliance.store_evidence'],
            );
        });

        $this->app->bind(CalculateTaxAction::class, function ($app) {
            return new CalculateTaxAction(
                rulesEngine: $app->make(RulesEngineService::class),
                events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            );
        });

        $this->app->bind(Actions\CalculateInvoiceTaxAction::class, function ($app) {
            return new Actions\CalculateInvoiceTaxAction(
                rulesEngine: $app->make(RulesEngineService::class),
                events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tax.php' => config_path('tax.php'),
        ], 'tax-engine-config');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'tax-engine-migrations');

        /** @var \Illuminate\Contracts\Events\Dispatcher $events */
        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);
        $events->listen(TaxCalculated::class, StoreComplianceSnapshotListener::class);
    }

    private function registerRules(): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app->make('config');
        /** @var array<string, bool> $rules */
        $rules = $config->get('tax.rules', []);

        $this->app->bind('tax-engine.rule.ioss', function ($app) use ($config) {
            return new IossRule(
                rates: $app->make(RateRepositoryContract::class),
                iossEnabled: (bool) $config->get('tax.ioss.enabled'),
                iossConsignmentMaxCents: (int) $config->get('tax.thresholds.ioss_consignment_max', 15000),
                iossExcludedCategories: (array) $config->get('tax.ioss.excluded_categories', []),
            );
        });

        $this->app->bind('tax-engine.rule.export', function () {
            return new ExportRule();
        });

        $this->app->bind('tax-engine.rule.service_export', function () {
            return new ServiceExportRule();
        });

        $this->app->bind('tax-engine.rule.reverse_charge', function () {
            return new ReverseChargeRule();
        });

        $this->app->bind('tax-engine.rule.oss', function ($app) use ($config) {
            return new OssRule(
                rates: $app->make(RateRepositoryContract::class),
                ossEnabled: (bool) $config->get('tax.oss.enabled'),
                ossThresholdCents: (int) $config->get('tax.thresholds.oss_micro_business', 1000000),
                turnoverRepository: $app->bound(Contracts\OssTurnoverRepositoryContract::class)
                    ? $app->make(Contracts\OssTurnoverRepositoryContract::class)
                    : null,
            );
        });

        $this->app->bind('tax-engine.rule.cross_border_b2c_fallback', function ($app) {
            return new CrossBorderB2CFallbackRule(
                rates: $app->make(RateRepositoryContract::class),
            );
        });

        $this->app->bind('tax-engine.rule.domestic_reverse_charge', function () {
            return new DomesticReverseChargeRule();
        });

        $this->app->bind('tax-engine.rule.domestic_standard', function ($app) {
            return new DomesticStandardRule(
                rates: $app->make(RateRepositoryContract::class),
            );
        });

        $ruleMap = [
            'ioss' => 'tax-engine.rule.ioss',
            'export' => 'tax-engine.rule.export',
            'service_export' => 'tax-engine.rule.service_export',
            'reverse_charge' => 'tax-engine.rule.reverse_charge',
            'oss' => 'tax-engine.rule.oss',
            'cross_border_b2c_fallback' => 'tax-engine.rule.cross_border_b2c_fallback',
            'domestic_reverse_charge' => 'tax-engine.rule.domestic_reverse_charge',
            'domestic_standard' => 'tax-engine.rule.domestic_standard',
        ];

        foreach ($ruleMap as $key => $abstract) {
            if ($rules[$key] ?? true) {
                $this->app->tag($abstract, 'tax-engine.rules');
            }
        }
    }

    private function registerPolicies(): void
    {
        $this->app->singleton(Contracts\PlaceOfSupplyPolicyContract::class, function () {
            return new Policies\DefaultPlaceOfSupplyPolicy();
        });

        $this->app->singleton(Contracts\RoundingPolicyContract::class, function ($app) {
            $strategy = Enums\RoundingStrategy::tryFrom(
                $app['config']['tax.rounding.strategy'] ?? 'per_line'
            ) ?? Enums\RoundingStrategy::PerLine;

            return new Policies\DefaultRoundingPolicy($strategy);
        });

        $this->app->singleton(Contracts\ValidationPolicyContract::class, function () {
            return new Policies\DefaultValidationPolicy();
        });
    }
}
