# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.0] - 2026-08-05

### Added
- Stock Expiration & Lot/Batch Number Tracking architecture (`BatchLot` entity & `BatchLotRepository`).
- First Expired, First Out (FEFO) stock allocation engine (`BatchLotManager::allocateFefoStock()`).
- Expiration tracking queries and near-expiration alerting (`findExpiringLots()`).
- REST API Batch Lot endpoints (`GET/POST /api/v1/batch-lots`, `POST /api/v1/batch-lots/fefo-fulfill`, `GET /api/v1/batch-lots/expiring`).
- Automated PHPUnit tests for FEFO lot picking order, expiration sorting, and stock deductions (19 tests, 75 assertions passing).

## [1.6.0] - 2026-08-05

### Added
- GitHub Actions CI Continuous Integration workflow (`.github/workflows/ci.yml`).
- Automated CI pipeline running `composer validate --strict`, `doctrine:schema:validate`, and full test suite.

## [1.5.0] - 2026-08-05

### Added
- Automated Purchase Order Reordering Subsystem (`Supplier`, `PurchaseOrder`, and `PurchaseOrderItem` entities).
- Event-driven Purchase Order draft generation (`ReorderEventSubscriber` listening to `LowStockEvent`).

## [1.4.0] - 2026-08-05

### Added
- User authentication and Security entity (`User` implementing `UserInterface` & `PasswordAuthenticatedUserInterface`).
- Bearer token authentication engine (`TokenAuthenticator`).
- Role-Based Access Control (RBAC) enforcing permissions (`ROLE_ADMIN`, `ROLE_WAREHOUSE`, `ROLE_VIEWER`).

## [1.3.0] - 2026-08-05

### Added
- Bulk CSV Import Service (`CsvBatchImporter`) and Streamed CSV Exporter (`CsvExporter`).

## [1.2.0] - 2026-08-05

### Added
- Multi-warehouse location management architecture (`Warehouse` and `WarehouseStock` entities).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).
- Webhook subscription system (`WebhookSubscription` entity & repository).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Doctrine ORM entities: `Category`, `Product`, and `StockMovement`.
- Standard MIT License.
