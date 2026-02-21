# Tax Engine

A Laravel package for EU tax/VAT calculation with support for OSS, IOSS, reverse charge, and export rules.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- `ext-bcmath`

## Installation

```bash
composer require veltix/tax-engine
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=tax-engine-config
```

If using database storage for compliance snapshots, publish and run migrations:

```bash
php artisan vendor:publish --tag=tax-engine-migrations
php artisan migrate
```

## Configuration

Key settings in `config/tax.php`:

| Key | Description | Default |
|-----|-------------|---------|
| `seller.country` | Seller's EU country code | `NL` |
| `oss.enabled` | Enable One-Stop Shop | `false` |
| `ioss.enabled` | Enable Import One-Stop Shop | `false` |
| `rounding.mode` | Rounding mode (`half_up`, `half_down`, `half_even`) | `half_up` |
| `compliance.store_decisions` | Store tax decisions | `true` |
| `compliance.store_evidence` | Store evidence (requires `store_decisions` with database driver) | `true` |
| `compliance.storage_driver` | Storage backend (`array` or `database`) | `array` |

## Usage

```php
use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

$action = app(CalculateTaxAction::class);

$result = $action->execute(new TransactionData(
    transactionId: 'order-123',
    amount: Money::fromCents(10000), // EUR 100.00
    sellerCountry: new Country('DE'),
    buyerCountry: new Country('FR'),
    customerType: CustomerType::B2C,
    supplyType: SupplyType::DigitalServices,
));

$result->taxAmount;    // Money object with calculated tax
$result->grossAmount;  // Net + tax
$result->decision;     // TaxDecisionData with scheme, rate, reasoning
```

## Available Rules

Rules are evaluated by priority (highest first):

| Rule | Priority | Description |
|------|----------|-------------|
| IOSS | 70 | Import of goods into EU (max EUR 150) |
| Export | 60 | Goods export from EU to non-EU |
| Service Export | 59 | Services from EU to non-EU (outside scope) |
| Reverse Charge | 50 | B2B cross-border EU with VAT number |
| OSS | 40 | B2C cross-border EU (when OSS enabled) |
| Domestic Reverse Charge | 30 | Domestic B2B reverse charge |
| Cross-Border B2C Fallback | 20 | B2C cross-border EU without OSS (seller rate) |
| Domestic Standard | 10 | Domestic EU sale |

## Extending with Custom Rules

Implement `RuleContract` and tag it in your service provider:

```php
use Veltix\TaxEngine\Contracts\RuleContract;

class MyCustomRule implements RuleContract
{
    public function applies(TransactionData $transaction): bool { /* ... */ }
    public function evaluate(TransactionData $transaction): TaxDecisionData { /* ... */ }
    public function priority(): int { return 25; }
}

// In your service provider:
$this->app->bind(MyCustomRule::class, fn () => new MyCustomRule());
$this->app->tag(MyCustomRule::class, 'tax-engine.rules');
```

## License

MIT
