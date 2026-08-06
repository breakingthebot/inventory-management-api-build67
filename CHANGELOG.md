# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.19.0] - 2026-08-05

### Added
- Event-Sourced Entity Revision History & Audit Trail Engine (`EntityRevision` entity & `EntityRevisionRepository`).
- Point-in-time snapshot recorder and state rollback engine (`AuditTrailEngine::rollbackEntityToRevision()`).
- REST API Revision endpoints (`GET /api/v1/revisions`, `POST /api/v1/revisions/{id}/rollback`).
- Automated PHPUnit tests for revision snapshot recording, state serialization, timeline queries, and state rollbacks (43 tests, 176 assertions passing).

## [1.18.0] - 2026-08-05

### Added
- Automated Supplier Performance & Lead Time Analytics Engine (`SupplierMetrics` entity & `SupplierMetricsRepository`).
- Data-driven vendor scorecard calculation engine (`SupplierAnalyticsEngine`).

## [1.17.0] - 2026-08-05

### Added
- Automated Backorder Queue & Stock Allocation Engine (`Backorder` entity & `BackorderRepository`).

## [1.16.0] - 2026-08-05

### Added
- Product Variant Matrix & SKU Options Subsystem (`ProductOption` & `ProductVariant` entities).

## [1.15.0] - 2026-08-05

### Added
- Custom Export Report Builder Subsystem (`ReportGenerator` service & `ReportController`).

## [1.14.0] - 2026-08-05

### Added
- Full Multi-Tenant Account & Organization Isolation Subsystem (`Tenant` entity & `TenantRepository`).

## [1.13.0] - 2026-08-05

### Added
- Stock Reservation Engine for E-Commerce Checkout (`StockReservation` entity & `StockReservationRepository`).

## [1.12.0] - 2026-08-05

### Added
- Webhook Failure Retry Queue & Circuit Breaker Subsystem (`WebhookRetryQueue` entity & `WebhookRetryQueueRepository`).

## [1.11.0] - 2026-08-05

### Added
- Automated Inventory Audit Sampling & Count Reconciliation Subsystem (`AuditCycle` & `AuditDiscrepancy` entities).

## [1.10.0] - 2026-08-05

### Added
- Multi-Currency Pricing & Regional Tax Rate Matrix architecture (`CurrencyRate` & `TaxZone` entities).

## [1.9.0] - 2026-08-05

### Added
- API Rate Limiting & Sliding Window Throttle subsystem (`RateLimiter` service & `RateLimitSubscriber`).

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
