# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-08-05

### Added
- Automated Purchase Order Reordering Subsystem (`Supplier`, `PurchaseOrder`, and `PurchaseOrderItem` entities).
- Event-driven Purchase Order draft generation (`ReorderEventSubscriber` listening to `LowStockEvent`).
- Dynamic reorder quantity formula `max(10, (minStockLevel * 2) - currentStock)`.
- Goods receiving engine (`PurchaseOrderGenerator::receiveGoods()`) automatically updating PO status to `RECEIVED` and adding received stock into inventory.
- REST API Supplier management endpoints (`GET/POST /api/v1/suppliers`).
- REST API Purchase Order endpoints (`GET /api/v1/purchase-orders`, `GET /api/v1/purchase-orders/{id}`, `POST /api/v1/purchase-orders/{id}/receive`).
- Automated PHPUnit tests for PO reorder calculations, draft generation, and receiving shipments (18 tests, 67 assertions passing).

## [1.4.0] - 2026-08-05

### Added
- User authentication and Security entity (`User` implementing `UserInterface` & `PasswordAuthenticatedUserInterface`).
- Bearer token authentication engine (`TokenAuthenticator`) issuing signed token payloads.
- Role-Based Access Control (RBAC) enforcing permissions for `ROLE_ADMIN`, `ROLE_WAREHOUSE`, and `ROLE_VIEWER`.
- REST API Auth Endpoints (`POST /api/v1/auth/login` and `GET /api/v1/auth/me`).

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
