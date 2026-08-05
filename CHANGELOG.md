# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-08-05

### Added
- Bulk CSV Import Service (`CsvBatchImporter`) with per-row validation and multi-error aggregation.
- Streamed CSV Exporter (`CsvExporter`) generating product catalog and stock movement audit log downloads.
- REST API endpoint `POST /api/v1/products/import/csv` supporting file uploads or raw CSV string imports.
- REST API endpoints `GET /api/v1/products/export/csv` and `GET /api/v1/stock-movements/export/csv`.
- Chaos Fixture automated unit tests verifying multi-error reporting without batch failure (14 tests, 53 assertions passing).

## [1.2.0] - 2026-08-05

### Added
- Multi-warehouse location management architecture (`Warehouse` and `WarehouseStock` entities).
- Location-specific stock adjustments (`POST /api/v1/warehouses/{id}/stock`).
- Inter-warehouse stock transfer engine (`POST /api/v1/warehouses/transfer`).
- Per-warehouse stock rollup aggregation syncing local warehouse quantities to global `Product` total stock.
- Location-aware stock audit trail logging bound to `StockMovement` records.
- REST API Warehouse endpoints (`GET/POST /api/v1/warehouses`, `GET /api/v1/warehouses/{id}`).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).
- Webhook subscription system (`WebhookSubscription` entity & repository).
- Cryptographic HMAC-SHA256 signature generation (`X-Inventory-Signature`).
- Outbound Notification Audit Logging (`NotificationLog` entity & repository).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Doctrine ORM entities: `Category`, `Product`, and `StockMovement`.
- Auto-recalculating product stock status (`IN_STOCK`, `LOW_STOCK`, `OUT_OF_STOCK`).
- `StockManager` domain service for atomic stock adjustments (IN, OUT, ADJUST).
- Standard MIT License.
