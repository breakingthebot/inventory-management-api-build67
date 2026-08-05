# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- PHPUnit test suite (7 tests, 23 assertions passing).
- Standard MIT License.
