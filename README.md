# Inventory Management API — Symfony & Doctrine ORM

A high-performance RESTful Inventory Management API built with Symfony 6.4 and PHP 8.3 featuring Doctrine ORM entity mappings, multi-warehouse location tracking, inter-warehouse stock transfers, streaming CSV bulk import and export, automatic stock status recalculation, low-stock event dispatches, HMAC-signed webhooks, notification audit logging, input validation, serialization group contexts, and full CRUD operations.

## Stack
- **Language & Runtime**: PHP 8.3
- **Framework**: Symfony 6.4 (Microkernel architecture)
- **ORM & Database**: Doctrine ORM 3.x with SQLite DBAL
- **Bulk Data Processing**: Streaming CSV Importer (`CsvBatchImporter`) with multi-row validation error aggregation & CSV Exporter (`CsvExporter`)
- **Multi-Warehouse**: Per-location stock tracking (`Warehouse`, `WarehouseStock`) & stock transfers
- **Event Management**: Symfony EventDispatcher (`LowStockEvent`, `LowStockSubscriber`)
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

Access the API in your browser or HTTP client at: `http://127.0.0.1:8000/api/v1/health`

---

## REST API Documentation

### Core Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/health` | Health check & diagnostic status |
| `GET` | `/api/v1/categories` | List all categories |
| `POST` | `/api/v1/categories` | Create a new category |
| `GET` | `/api/v1/products` | Search & list products (supports `?q=`, `?category_id=`, `?status=`) |
| `POST` | `/api/v1/products` | Create a new product |
| `GET` | `/api/v1/products/{id}` | Get single product detail with stock movement audit trail |
| `PUT` | `/api/v1/products/{id}` | Update product information |
| `DELETE` | `/api/v1/products/{id}` | Delete a product |
| `POST` | `/api/v1/products/{id}/stock` | Record stock adjustment (`IN`, `OUT`, `ADJUST`) |
| `POST` | `/api/v1/products/import/csv` | Bulk import products from CSV spreadsheet |
| `GET` | `/api/v1/products/export/csv` | Download CSV catalog export of products |
| `GET` | `/api/v1/stock-movements/export/csv` | Download CSV audit log of stock movements |
| `GET` | `/api/v1/warehouses` | List all physical warehouses |
| `POST` | `/api/v1/warehouses` | Create a new warehouse facility |
| `GET` | `/api/v1/warehouses/{id}` | Get warehouse details and stock inventory |
| `POST` | `/api/v1/warehouses/{id}/stock` | Adjust stock for specific warehouse |
| `POST` | `/api/v1/warehouses/transfer` | Transfer stock between source & target warehouse |
| `GET` | `/api/v1/webhooks/subscriptions` | List active webhook subscribers |
| `POST` | `/api/v1/webhooks/subscriptions` | Register new webhook subscriber URL |
| `DELETE` | `/api/v1/webhooks/subscriptions/{id}` | Delete a webhook subscription |
| `GET` | `/api/v1/notifications/logs` | Inspect outbound notification & webhook logs |

---

## Architecture Notes

I structured this application around a clean separation of concerns using Symfony's microkernel pattern and Doctrine ORM. 

- **Domain Entities**: `Product`, `Category`, `Warehouse`, `WarehouseStock`, `StockMovement`, `WebhookSubscription`, and `NotificationLog`.
- **Bulk CSV Importer**: `CsvBatchImporter` parses CSV streams row by row, validating each record against domain constraints. Invalid rows are recorded in a detailed error summary report, ensuring valid records are safely committed without abandoning the batch.
- **Multi-Warehouse Management**: `WarehouseManager` coordinates per-location stock tracking (`WarehouseStock`) and executes inter-warehouse transfers (`transferStock()`).
- **Notification Pipeline**: `LowStockSubscriber` listens to `LowStockEvent` and triggers `NotificationService`, sending alert emails and HMAC-SHA256 signed Webhooks (`X-Inventory-Signature`).

---

## Testing

Run the full PHPUnit test suite:

```bash
php vendor/phpunit/phpunit/phpunit
```

---

## Data Handling & Privacy

- **Data Collected**: Stores product inventory metadata, category definitions, warehouse facility locations, per-location stock levels, stock movement logs, webhook subscriber URLs/secrets, and outbound alert delivery logs.
- **Data Persistence**: All records persist locally in configured SQLite database files (`var/app.db`).
- **Secrets & Keys**: Environment variables live in `.env` and are strictly excluded from git version control.

---

## License

Licensed under the [MIT License](LICENSE).
