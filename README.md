# Inventory Management API — Symfony & Doctrine ORM

A high-performance RESTful Inventory Management API built with Symfony 6.4 and PHP 8.3 featuring Doctrine ORM entity mappings, Automated Purchase Order (PO) Reordering, Supplier Management, Bearer Token Authentication, Role-Based Access Control (RBAC), multi-warehouse location tracking, inter-warehouse stock transfers, streaming CSV bulk import/export, automatic stock status recalculation, low-stock event dispatches, HMAC-signed webhooks, notification audit logging, input validation, serialization group contexts, and full CRUD operations.

## Stack
- **Language & Runtime**: PHP 8.3
- **Framework**: Symfony 6.4 (Microkernel architecture)
- **ORM & Database**: Doctrine ORM 3.x with SQLite DBAL
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

Access the API in your browser or HTTP client at: `http://127.0.0.1:8000/api/v1/health`

---

## Seeded User Credentials (RBAC Roles)

The API automatically provisions default accounts for testing:

| Role | Email | Password | Permissions |
| --- | --- | --- | --- |
| **Admin** | `admin@inventory.internal` | `AdminPass123!` | Full site management, product/category CRUD, warehouse management, PO management, webhooks, imports. |
| **Warehouse** | `warehouse@inventory.internal` | `WorkerPass123!` | Product updates, stock adjustments, inter-warehouse transfers, PO receiving, CSV bulk imports. |
| **Viewer** | `auditor@inventory.internal` | `AuditorPass123!` | Read-only access to all API GET endpoints. |

---

## REST API Documentation

### Auth, Inventory & PO Endpoints

| Method | Endpoint | Authorization | Description |
| --- | --- | --- | --- |
| `POST` | `/api/v1/auth/login` | Public | Authenticate user and receive Bearer token |
| `GET` | `/api/v1/auth/me` | Bearer Token | View active authenticated user profile |
| `GET` | `/api/v1/health` | Public | Health check & diagnostic status |
| `GET` | `/api/v1/categories` | Public / Viewer | List all categories |
| `POST` | `/api/v1/categories` | `ROLE_ADMIN` | Create a new category |
| `GET` | `/api/v1/products` | Public / Viewer | Search & list products (supports `?q=`, `?category_id=`, `?status=`) |
| `POST` | `/api/v1/products` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Create a new product |
| `GET` | `/api/v1/products/{id}` | Public / Viewer | Get single product detail |
| `PUT` | `/api/v1/products/{id}` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Update product information |
| `DELETE` | `/api/v1/products/{id}` | `ROLE_ADMIN` | Delete a product |
| `POST` | `/api/v1/products/{id}/stock` | `ROLE_ADMIN` / `ROLE_WAREHOUSE` | Record stock adjustment (`IN`, `OUT`, `ADJUST`) |
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
| `GET` | `/api/v1/webhooks/subscriptions` | Public / Viewer | List active webhook subscribers |
| `POST` | `/api/v1/webhooks/subscriptions` | `ROLE_ADMIN` | Register new webhook subscriber URL |
| `GET` | `/api/v1/notifications/logs` | Public / Viewer | Inspect outbound notification & webhook logs |

---

## Architecture Notes

I structured this application around a clean separation of concerns using Symfony's microkernel pattern and Doctrine ORM. 

- **Automated PO Reordering**: When stock deductions transition items into `LOW_STOCK`, `ReorderEventSubscriber` invokes `PurchaseOrderGenerator` to calculate reorder quantities `max(10, (minStockLevel * 2) - currentStock)` and creates or appends line items to `DRAFT` Purchase Orders.
- **Goods Receiving Engine**: Receiving PO shipments (`POST /api/v1/purchase-orders/{id}/receive`) automatically updates PO status to `RECEIVED` and invokes `StockManager` or `WarehouseManager` to restock physical inventory.
- **Security & RBAC**: `User` entity implementing `UserInterface` and `PasswordAuthenticatedUserInterface`. `TokenAuthenticator` generates and validates HMAC-signed Bearer tokens (`Authorization: Bearer <token>`).

---

## Testing

Run the full PHPUnit test suite:

```bash
php vendor/phpunit/phpunit/phpunit
```

---

## Data Handling & Privacy

- **Data Collected**: Stores user accounts (hashed bcrypt passwords), product inventory metadata, category definitions, warehouse facility locations, per-location stock levels, stock movement logs, supplier definitions, purchase orders, webhook subscriber URLs/secrets, and outbound alert delivery logs.
- **Data Persistence**: All records persist locally in configured SQLite database files (`var/app.db`).
- **Secrets & Keys**: Environment variables live in `.env` and are strictly excluded from git version control.

---

## License

Licensed under the [MIT License](LICENSE).
