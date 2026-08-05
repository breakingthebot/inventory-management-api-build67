# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-08-05

### Added
- Multi-warehouse location management architecture (`Warehouse` and `WarehouseStock` entities).
- Location-specific stock adjustments (`POST /api/v1/warehouses/{id}/stock`).
- Inter-warehouse stock transfer engine (`POST /api/v1/warehouses/transfer`) with automatic stock conservation checks.
- Per-warehouse stock rollup aggregation syncing local warehouse quantities to global `Product` total stock.
- Location-aware stock audit trail logging bound to `StockMovement` records.
- REST API Warehouse endpoints (`GET/POST /api/v1/warehouses`, `GET /api/v1/warehouses/{id}`).
- Automated PHPUnit tests for `WarehouseManager` stock allocations, transfers, and boundary errors (12 tests, 39 assertions passing).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).
- Webhook subscription system (`WebhookSubscription` entity & repository) supporting event filters (`inventory.low_stock`).
- Cryptographic HMAC-SHA256 signature generation (`X-Inventory-Signature`) for secure webhook payload validation.
- Outbound Notification Audit Logging (`NotificationLog` entity & repository).
- REST API Webhook endpoints (`GET/POST/DELETE /api/v1/webhooks/subscriptions`).
- REST API Notification Logs endpoint (`GET /api/v1/notifications/logs`).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Doctrine ORM entities: `Category`, `Product`, and `StockMovement`.
- Auto-recalculating product stock status (`IN_STOCK`, `LOW_STOCK`, `OUT_OF_STOCK`).
- `StockManager` domain service for atomic stock adjustments (IN, OUT, ADJUST).
- Full RESTful CRUD endpoints for Products (`/api/v1/products`), Categories (`/api/v1/categories`), and Stock Adjustments (`/api/v1/products/{id}/stock`).
- Standard MIT License.
