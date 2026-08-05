# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.10.0] - 2026-08-05

### Added
- Multi-Currency Pricing & Regional Tax Rate Matrix architecture (`CurrencyRate` & `TaxZone` entities).
- Currency conversion and gross/net tax calculation engine (`CurrencyConverter`).
- Default seeded currencies (`USD`, `EUR`, `GBP`, `CAD`) and regional tax zones (`US-CA`, `EU-DE`, `UK-VAT`).
- REST API Currency endpoints (`GET /api/v1/currencies`, `POST /api/v1/currencies/update`, `GET /api/v1/tax-zones`, `GET /api/v1/products/{id}/price`).
- Automated PHPUnit tests for exchange rate conversion math, tax matrix additions, and formatted currency outputs (23 tests, 100 assertions passing).

## [1.9.0] - 2026-08-05

### Added
- API Rate Limiting & Sliding Window Throttle subsystem (`RateLimiter` service & `RateLimitSubscriber`).
- 60 requests per minute sliding window quota tracking per Client IP / Bearer Token payload.
- HTTP `429 Too Many Requests` status code enforcement.

## [1.8.0] - 2026-08-05

### Added
- Interactive Operations Admin Dashboard UI (`DashboardController` rendering `dashboard/index.html.twig`).
- Real-time catalog valuation calculation, stock status health breakdown progress bar, active warehouse metrics, and pending PO status counters.

## [1.7.0] - 2026-08-05

### Added
- Stock Expiration & Lot/Batch Number Tracking architecture (`BatchLot` entity & `BatchLotRepository`).
- First Expired, First Out (FEFO) stock allocation engine (`BatchLotManager::allocateFefoStock()`).

## [1.6.0] - 2026-08-05

### Added
- GitHub Actions CI Continuous Integration workflow (`.github/workflows/ci.yml`).

## [1.5.0] - 2026-08-05

### Added
- Automated Purchase Order Reordering Subsystem (`Supplier`, `PurchaseOrder`, and `PurchaseOrderItem` entities).

## [1.4.0] - 2026-08-05

### Added
- User authentication and Security entity (`User` implementing `UserInterface` & `PasswordAuthenticatedUserInterface`).

## [1.3.0] - 2026-08-05

### Added
- Bulk CSV Import Service (`CsvBatchImporter`) and Streamed CSV Exporter (`CsvExporter`).

## [1.2.0] - 2026-08-05

### Added
- Multi-warehouse location management architecture (`Warehouse` and `WarehouseStock` entities).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Standard MIT License.
