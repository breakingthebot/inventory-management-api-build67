# Inventory Management API — Symfony & Doctrine ORM

[![Inventory Management API CI](https://github.com/breakingthebot/inventory-management-api-build67/actions/workflows/ci.yml/badge.svg)](https://github.com/breakingthebot/inventory-management-api-build67/actions/workflows/ci.yml)

A high-performance RESTful Inventory Management API built with Symfony 6.4 and PHP 8.3 featuring Doctrine ORM entity mappings, Automated Backorder Queue & Allocation Engine (`Backorder`, `BackorderManager`), Product Variant Matrix & SKU Options Engine (`ProductOption`, `ProductVariant`, `VariantManager`), Custom Export Report Builder (HTML-PDF Valuation Statements & SpreadsheetML Excel XML), Full Multi-Tenant Organization Isolation (`Tenant`, `TenantContext`, `X-Tenant-Code` headers), E-Commerce Stock Reservation Engine (TTL Cart Holds & Oversell Prevention), Webhook Failure Retry Queue & Circuit Breaker, Automated Inventory Audit Sampling & Stock Reconciliation, Multi-Currency Conversions (`USD`, `EUR`, `GBP`, `CAD`), Regional Tax Rate Matrix (`US-CA`, `EU-DE`, `UK-VAT`), API Rate Limiting & Sliding Window Throttling, Interactive Operations Admin Dashboard UI, Batch/Lot Number Tracking, First Expired First Out (FEFO) allocation, GitHub Actions CI/CD pipeline, Automated Purchase Order (PO) Reordering, Supplier Management, Bearer Token Authentication, Role-Based Access Control (RBAC), multi-warehouse location tracking, inter-warehouse stock transfers, streaming CSV bulk import/export, automatic stock status recalculation, low-stock event dispatches, HMAC-signed webhooks, notification audit logging, input validation, serialization group contexts, and full CRUD operations.

## Stack
- **Language & Runtime**: PHP 8.3
- **Framework**: Symfony 6.4 (Microkernel architecture)
- **Backorder Allocation Engine**: `Backorder`, `BackorderManager` (FIFO priority queue, automatic restock allocation)
- **Product Variant Matrix**: `ProductOption`, `ProductVariant`, `VariantManager` (Parent-child SKUs, option mappings `{"color":"Red","size":"XL"}`, price overrides)
- **Report Generation Engine**: `ReportGenerator`, Twig PDF Valuation templates, SpreadsheetML XML Excel Exports
- **Multi-Tenant Architecture**: `Tenant`, `TenantContext`, `TenantSubscriber` (Header `X-Tenant-Code` or User profile tenant resolution)
- **Stock Reservation Engine**: `StockReservation`, `StockReservationEngine` (15-min TTL cart holds, $Available = Physical - Held$ math)
- **Webhook Retry & Circuit Breaker**: `WebhookRetryQueue`, `WebhookRetryEngine` ($10 \times 2^{n-1}$ exponential backoff, circuit breaker trip at 5 failures)
- **Inventory Audit Engine**: `AuditCycle`, `AuditDiscrepancy`, `AuditManager` (random sampling & count reconciliations)
- **Multi-Currency & Tax Engine**: `CurrencyRate`, `TaxZone`, `CurrencyConverter` (`USD`, `EUR`, `GBP`, `CAD`, `US-CA`, `EU-DE`, `UK-VAT`)
- **Rate Limiting & Security**: Sliding Window Rate Limiter (`RateLimiter`, `RateLimitSubscriber`), 60 requests/min quota, `429 Too Many Requests` status, `X-RateLimit-*` headers
- **Frontend UI & Templating**: Twig (`symfony/twig-bundle`), Glassmorphism CSS, Inter Typography
- **ORM & Database**: Doctrine ORM 3.x with SQLite DBAL
- **Batch & FEFO Engine**: `BatchLot` entity, `BatchLotRepository` (FEFO queries), and `BatchLotManager`
- **Continuous Integration**: GitHub Actions CI (`.github/workflows/ci.yml`)
- **Automated PO System**: `Supplier`, `PurchaseOrder`, `PurchaseOrderItem`, `PurchaseOrderGenerator`, and `ReorderEventSubscriber`
- **Authentication & RBAC**: `User` entity, `TokenAuthenticator` service, Bearer Token authorization (`ROLE_ADMIN`, `ROLE_WAREHOUSE`, `ROLE_VIEWER`)
- **Bulk Data Processing**: Streaming CSV Importer (`CsvBatchImporter`) & CSV Exporter (`CsvExporter`)
- **Multi-Warehouse**: Per-location stock tracking (`Warehouse`, `WarehouseStock`) & stock transfers
- **Event Management**: Symfony EventDispatcher (`LowStockEvent`, `LowStockSubscriber`, `ReorderEventSubscriber`)
- **Security & Webhooks**: Outbound HTTP Webhooks signed with HMAC-SHA256
- **Validation**: Symfony Validator
- **Serializer**: Symfony Serializer (Group contexts)
- **Testing**: PHPUnit 10.5
- **Version Control**: Git & GitHub Actions CI

---

## Setup & Running Locally

### Prerequisites
- PHP 8.3 or higher with `pdo_sqlite`, `mbstring`, `openssl`, and `zip` extensions enabled.
- Composer 2.x

### Quickstart Commands

```bash
# 1. Clone the repository
git clone https://github.com/breakingthebot/inventory-management-api-build67.git
cd inventory-management-api-build67

# 2. Install PHP dependencies
composer install

# 3. Copy environment configuration
cp .env.example .env

# 4. Create / update SQLite database schema
php bin/console doctrine:schema:update --force

# 5. Run automated PHPUnit test suite
php vendor/phpunit/phpunit/phpunit

# 6. Start local Symfony development server
php -S 127.0.0.1:8000 -t public
```

Access the Web Dashboard in your browser: `http://127.0.0.1:8000/admin/dashboard`  
Access API Health Check: `http://127.0.0.1:8000/api/v1/health`

---

## REST API & Web UI Documentation

### Web Dashboard & API Endpoints

| Method | Endpoint | Authorization | Description |
| --- | --- | --- | --- |
| `GET` | `/admin/dashboard` | Web Browser | Interactive Admin Dashboard UI with metrics & FEFO alerts |
| `GET` | `/api/v1/backorders` | Public / Viewer | List queued backorders (filter `?status=`, `?product_id=`) |
| `POST` | `/api/v1/backorders` | Public | Place a backorder request for an out-of-stock item |
| `POST` | `/api/v1/backorders/{id}/cancel` | Public | Cancel a pending backorder request |
| `GET` | `/api/v1/products/{id}/variants` | Public / Viewer | List all child variant SKUs for a parent catalog product |
| `POST` | `/api/v1/products/{id}/variants` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Create child variant SKU with option values & price overrides |
| `POST` | `/api/v1/variants/{id}/stock` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Record stock adjustment for a specific variant SKU |
| `GET` | `/api/v1/reports/valuation/pdf` | Public / Viewer | Stream HTML-PDF inventory valuation statement |
| `GET` | `/api/v1/reports/stock-movements/excel` | Public / Viewer | Download SpreadsheetML XML Excel stock audit log |
| `GET` | `/api/v1/tenants` | Public / Viewer | List multi-tenant organization accounts |
| `POST` | `/api/v1/tenants` | `ROLE_ADMIN` | Provision a new tenant organization (`FREE`, `PRO`, `ENTERPRISE`) |
| `POST` | `/api/v1/auth/login` | Public | Authenticate user and receive Bearer token |
| `GET` | `/api/v1/auth/me` | Bearer Token | View active authenticated user profile |
| `GET` | `/api/v1/health` | Public | Health check & diagnostic status |
| `GET` | `/api/v1/categories` | Public / Viewer | List all categories |
| `POST` | `/api/v1/categories` | `ROLE_ADMIN` | Create a new category |
| `GET` | `/api/v1/products` | Public / Viewer | Search & list products (supports `?q=`, `?category_id=`, `?status=`) |
| `POST` | `/api/v1/products` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Create a new product |
| `GET` | `/api/v1/products/{id}` | Public / Viewer | Get single product detail |
| `GET` | `/api/v1/products/{id}/price` | Public / Viewer | Get product localized price & tax breakdown (`?currency=EUR&tax_zone=EU-DE`) |
| `PUT` | `/api/v1/products/{id}` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Update product information |
| `DELETE` | `/api/v1/products/{id}` | `ROLE_ADMIN` | Delete a product |
| `POST` | `/api/v1/products/{id}/stock` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Record stock adjustment (`IN`, `OUT`, `ADJUST`) |
| `POST` | `/api/v1/reservations` | Public | Reserve stock for 15-min shopping cart checkout hold |
| `GET` | `/api/v1/reservations/{token}` | Public | Inspect reservation status & remaining TTL |
| `POST` | `/api/v1/reservations/{token}/confirm` | Public | Confirm checkout reservation and deduct physical inventory |
| `POST` | `/api/v1/reservations/{token}/cancel` | Public | Manually release held stock reservation |
| `GET` | `/api/v1/webhooks/subscriptions` | Public / Viewer | List active webhook subscribers |
| `POST` | `/api/v1/webhooks/subscriptions` | `ROLE_ADMIN` | Register new webhook subscriber URL |
| `GET` | `/api/v1/webhooks/retries` | Public / Viewer | List webhook retry queue items |
| `POST` | `/api/v1/webhooks/retries/process` | `ROLE_ADMIN` | Execute batch processing of due webhook retries |
| `GET` | `/api/v1/notifications/logs` | Public / Viewer | Inspect outbound notification & webhook logs |
| `GET` | `/api/v1/audits` | Public / Viewer | List physical inventory audit cycles |
| `POST` | `/api/v1/audits` | `ROLE_ADMIN` | Generate random inventory audit sampling cycle |
| `GET` | `/api/v1/audits/{id}` | Public / Viewer | Get audit details with discrepancy items |
| `POST` | `/api/v1/audits/{id}/reconcile` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Submit physical counts and reconcile inventory variances |
| `GET` | `/api/v1/currencies` | Public / Viewer | List supported currency exchange rates |
| `POST` | `/api/v1/currencies/update` | `ROLE_ADMIN` | Update or set currency exchange rate |
| `GET` | `/api/v1/tax-zones` | Public / Viewer | List regional tax rate zones |
| `GET` | `/api/v1/batch-lots` | Public / Viewer | List batch lots (filter `?product_id=`) |
| `POST` | `/api/v1/batch-lots` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Create batch lot for a product |
| `POST` | `/api/v1/batch-lots/fefo-fulfill` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Deduct stock using First Expired First Out (FEFO) strategy |
| `GET` | `/api/v1/batch-lots/expiring` | Public / Viewer | Get near-expiration batch lots report (`?days=30`) |
| `GET` | `/api/v1/suppliers` | Public / Viewer | List vendor suppliers |
| `POST` | `/api/v1/suppliers` | `ROLE_ADMIN` | Register a new supplier |
| `GET` | `/api/v1/purchase-orders` | Public / Viewer | List Purchase Orders (filter `?status=`) |
| `GET` | `/api/v1/purchase-orders/{id}` | Public / Viewer | Get Purchase Order details with line items |
| `POST` | `/api/v1/purchase-orders/{id}/receive` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Receive goods shipment and auto-restock inventory |
| `POST` | `/api/v1/products/import/csv` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Bulk import products from CSV spreadsheet |
| `GET` | `/api/v1/products/export/csv` | Public / Viewer | Download CSV catalog export |
| `GET` | `/api/v1/stock-movements/export/csv` | Public / Viewer | Download CSV stock audit log export |
| `GET` | `/api/v1/warehouses` | Public / Viewer | List all physical warehouses |
| `POST` | `/api/v1/warehouses` | `ROLE_ADMIN` | Create a new warehouse facility |
| `POST` | `/api/v1/warehouses/{id}/stock` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Adjust stock for specific warehouse |
| `POST` | `/api/v1/warehouses/transfer` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Transfer stock between warehouses |

---

## Architecture Notes

I structured this application around a clean separation of concerns using Symfony's microkernel pattern and Doctrine ORM. 

- **Backorder Allocation Engine**: `BackorderManager` manages queued customer demand in FIFO sequence (`createdAt` ASC) and automatically allocates restock shipments.
- **Product Variant Engine**: `VariantManager` manages child variant SKUs (`ProductVariant`), option mappings (e.g. `{"color":"Red", "size":"XL"}`), price overrides, and per-variant stock tracking.
- **Report Generator Engine**: `ReportGenerator` renders print-ready HTML valuation statements (`reports/valuation.html.twig`) and generates Microsoft SpreadsheetML XML workbooks for Excel export.
- **Multi-Tenant Architecture**: `TenantSubscriber` inspects incoming `X-Tenant-Code` headers or Bearer token user profiles to set `TenantContext`, ensuring isolated organization scoping.

---

## Testing

Run the full PHPUnit test suite:

```bash
php vendor/phpunit/phpunit/phpunit
```

---

## Data Handling & Privacy

- **Data Collected**: Stores tenant organization accounts, user accounts (hashed bcrypt passwords), product inventory metadata, product variant SKUs & option attributes, backorder queues & customer emails, category definitions, batch lots & expiration dates, warehouse facility locations, per-location stock levels, stock movement logs, supplier definitions, purchase orders, audit cycles & count discrepancies, stock reservations & TTL tokens, webhook retry queue entries, currency exchange rates, tax zones, webhook subscriber URLs/secrets, and outbound alert delivery logs.
- **Data Persistence**: All records persist locally in configured SQLite database files (`var/app.db`).
- **Secrets & Keys**: Environment variables live in `.env` and are strictly excluded from git version control.

---

## License

Licensed under the [MIT License](LICENSE).
