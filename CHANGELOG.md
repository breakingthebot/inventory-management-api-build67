# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.0] - 2026-08-05

### Added
- GitHub Actions CI Continuous Integration workflow (`.github/workflows/ci.yml`).
- Automated CI pipeline running `composer validate --strict`, `doctrine:schema:validate`, and full 18-test PHPUnit execution on every git push and pull request.
- CI status badge and CI build instructions in `README.md`.

## [1.5.0] - 2026-08-05

### Added
- Automated Purchase Order Reordering Subsystem (`Supplier`, `PurchaseOrder`, and `PurchaseOrderItem` entities).
- Event-driven Purchase Order draft generation (`ReorderEventSubscriber` listening to `LowStockEvent`).
- Dynamic reorder quantity formula `max(10, (minStockLevel * 2) - currentStock)`.
- Goods receiving engine (`PurchaseOrderGenerator::receiveGoods()`).

## [1.4.0] - 2026-08-05

### Added
- User authentication and Security entity (`User` implementing `UserInterface` & `PasswordAuthenticatedUserInterface`).
- Bearer token authentication engine (`TokenAuthenticator`) issuing signed token payloads.
- Role-Based Access Control (RBAC) enforcing permissions for `ROLE_ADMIN`, `ROLE_WAREHOUSE`, and `ROLE_VIEWER`.

## [1.3.0] - 2026-08-05

### Added
- Bulk CSV Import Service (`CsvBatchImporter`) with per-row validation and multi-error aggregation.
- Streamed CSV Exporter (`CsvExporter`) generating product catalog and stock movement audit log downloads.

## [1.2.0] - 2026-08-05

### Added
- Multi-warehouse location management architecture (`Warehouse` and `WarehouseStock` entities).
- Location-specific stock adjustments (`POST /api/v1/warehouses/{id}/stock`).
- Inter-warehouse stock transfer engine (`POST /api/v1/warehouses/transfer`).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).
- Webhook subscription system (`WebhookSubscription` entity & repository).
- Cryptographic HMAC-SHA256 signature generation (`X-Inventory-Signature`).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Doctrine ORM entities: `Category`, `Product`, and `StockMovement`.
- Standard MIT License.
