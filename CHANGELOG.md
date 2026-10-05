# Changelog

All notable changes to `veltix/tax-engine` are documented in this file.

## [1.1.0] - 2026-10-05

Requires PHP 8.3+ and Laravel 12 or 13; Laravel 11 users stay on 1.0.x
(1.0.1 is the last release that installs on Laravel 11 or PHP 8.2).

### Changed
- EU VAT rate table updated to version `2026.1` (rates in force on
  2026-10-05, checked against the European Commission's TEDB and the national
  tax authorities; sources in `docs/rates-sources.md`):
  - Romania: standard 19 % → 21 %; reduced 5 % and 9 % → a single 11 %
    (since 2025-08-01).
  - Slovakia: reduced 10 % → 5 % and 19 % (since 2025-01-01).
  - Estonia: second reduced rate of 13 % (since 2025-01-01).
  - Finland: second reduced rate 14 % → 13.5 % (since 2026-01-01).
  - Lithuania: second reduced rate 9 % → 12 % (since 2026-01-01).
  - Austria: super-reduced 4.9 % on basic foodstuffs (since 2026-07-01).
  - Cyprus: super-reduced 3 % added (in force since 2023, was missing).
  - Malta: parking rate 12 % added (long-standing, was missing).
- The table holds current rates only: a calculation for an earlier date still
  gets today's rate.
- Tested on PHP 8.3, 8.4 and 8.5 with Laravel 12 and 13.

### Fixed
- `EvidenceValidatorService::validate()` with no evidence throws
  `InsufficientEvidenceException` instead of a `TypeError`.
- PHPStan (level 8) passes again, so CI is green.

## [1.0.4] - 2026-09-30

### Fixed
- VIES answers it could not give (`userError` other than `VALID`/`INVALID`,
  e.g. `MS_UNAVAILABLE`) now throw `VatValidationException::serviceUnavailable()`
  instead of returning "invalid", so they are never cached.

### Added
- `CachingVatValidator::refresh()` and `VatValidatorService::validate(..., fresh: true)`
  ask past the cache and store the new answer.

## [1.0.3] - 2026-03-17

### Changed
- Dev dependency updates.

## [1.0.2] - 2026-03-17

### Changed
- Requires PHP 8.3+ and Laravel 12 or 13.

## [1.0.1] - 2026-02-21

### Changed
- Estonia's standard rate 22 % → 24 %.

## [1.0.0] - 2026-02-21

- Initial release.

[1.1.0]: https://github.com/veltix/tax-engine/compare/v1.0.4...v1.1.0
[1.0.4]: https://github.com/veltix/tax-engine/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/veltix/tax-engine/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/veltix/tax-engine/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/veltix/tax-engine/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/veltix/tax-engine/releases/tag/v1.0.0
