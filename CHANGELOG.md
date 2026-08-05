# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-08-05

### Added
- Low-Stock event dispatching pipeline (`LowStockEvent` & `LowStockSubscriber`).
- Webhook subscription system (`WebhookSubscription` entity & repository) supporting event filters (`inventory.low_stock`).
- Cryptographic HMAC-SHA256 signature generation (`X-Inventory-Signature`) for secure webhook payload validation.
- Outbound Notification Audit Logging (`NotificationLog` entity & repository) tracking dispatch status codes and JSON payloads.
- REST API Webhook endpoints (`GET/POST/DELETE /api/v1/webhooks/subscriptions`).
- REST API Notification Logs endpoint (`GET /api/v1/notifications/logs`).
- Automated PHPUnit tests for `NotificationService` HMAC verification and subscriber event dispatches (9 tests, 27 assertions passing).

## [1.0.0] - 2026-08-05

### Added
- Initial project architecture powered by Symfony 6.4 microkernel and PHP 8.3.
- Doctrine ORM entities: `Category`, `Product`, and `StockMovement`.
- Auto-recalculating product stock status (`IN_STOCK`, `LOW_STOCK`, `OUT_OF_STOCK`).
- `StockManager` domain service for atomic stock adjustments (IN, OUT, ADJUST).
- Full RESTful CRUD endpoints for Products (`/api/v1/products`), Categories (`/api/v1/categories`), and Stock Adjustments (`/api/v1/products/{id}/stock`).
- Input validation via Symfony Validator for SKU uniqueness, positive pricing, and boundary constraints.
- JSON Serialization with Symfony Serializer group contexts.
- Health check diagnostic endpoint (`GET /api/v1/health`).
- PHPUnit test suite.
- Standard MIT License.
