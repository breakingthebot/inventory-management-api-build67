# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.12.0] - 2026-08-05

### Added
- Webhook Failure Retry Queue & Circuit Breaker Subsystem (`WebhookRetryQueue` entity & `WebhookRetryQueueRepository`).
- Exponential backoff retry engine (`WebhookRetryEngine`) calculating $10 \times 2^{n-1}$ second delays (10s, 20s, 40s, 80s, 160s).
- Automated Circuit Breaker mechanism deactivating webhook subscriptions after 5 consecutive failed delivery attempts.
- REST API Webhook Retry endpoints (`GET /api/v1/webhooks/retries`, `POST /api/v1/webhooks/retries/process`).
- Automated PHPUnit tests for exponential backoff math calculations, retry scheduling, and circuit breaker tripping logic (27 tests, 124 assertions passing).

## [1.11.0] - 2026-08-05

### Added
- Automated Inventory Audit Sampling & Count Reconciliation Subsystem (`AuditCycle` & `AuditDiscrepancy` entities).
- Random product sampling cycle creation (`AuditManager::createAuditCycle()`).
- Automated stock count reconciliation engine posting `ADJUST` stock movements for physical variance items.

## [1.10.0] - 2026-08-05

### Added
- Multi-Currency Pricing & Regional Tax Rate Matrix architecture (`CurrencyRate` & `TaxZone` entities).
- Currency conversion and gross/net tax calculation engine (`CurrencyConverter`).

## [1.9.0] - 2026-08-05

### Added
- API Rate Limiting & Sliding Window Throttle subsystem (`RateLimiter` service & `RateLimitSubscriber`).
- 60 requests per minute sliding window quota tracking per Client IP / Bearer Token payload.

## [1.8.0] - 2026-08-05

### Added
- Interactive Operations Admin Dashboard UI (`DashboardController` rendering `dashboard/index.html.twig`).

## [1.7.0] - 2026-08-05

### Added
- Stock Expiration & Lot/Batch Number Tracking architecture (`BatchLot` entity & `BatchLotRepository`).

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
