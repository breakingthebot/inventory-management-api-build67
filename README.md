# Inventory Management API — Symfony & Doctrine ORM

A high-performance RESTful Inventory Management API built with Symfony 6.4 and PHP 8.3 featuring Doctrine ORM entity mappings, automatic stock status recalculation, low-stock event dispatches, HMAC-signed webhooks, notification audit logging, input validation, serialization group contexts, and full CRUD operations.

## Stack
- **Language & Runtime**: PHP 8.3
- **Framework**: Symfony 6.4 (Microkernel architecture)
- **ORM & Database**: Doctrine ORM 3.x with SQLite DBAL
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
| `GET` | `/api/v1/categories/{id}` | Get single category detail |
| `GET` | `/api/v1/products` | Search & list products (supports `?q=`, `?category_id=`, `?status=`) |
| `POST` | `/api/v1/products` | Create a new product |
| `GET` | `/api/v1/products/{id}` | Get single product detail with stock movement audit trail |
| `PUT` | `/api/v1/products/{id}` | Update product information |
| `DELETE` | `/api/v1/products/{id}` | Delete a product |
| `POST` | `/api/v1/products/{id}/stock` | Record stock adjustment (`IN`, `OUT`, `ADJUST`) |
| `GET` | `/api/v1/products/{id}/stock-movements` | Retrieve stock audit history for a product |
| `GET` | `/api/v1/webhooks/subscriptions` | List active webhook subscribers |
| `POST` | `/api/v1/webhooks/subscriptions` | Register new webhook subscriber URL |
| `DELETE` | `/api/v1/webhooks/subscriptions/{id}` | Delete a webhook subscription |
| `GET` | `/api/v1/notifications/logs` | Inspect outbound notification & webhook logs |

---

## Architecture Notes

I structured this application around a clean separation of concerns using Symfony's microkernel pattern and Doctrine ORM. 

- **Domain Entities**: `Product`, `Category`, `StockMovement`, `WebhookSubscription`, and `NotificationLog`. `Product` features lifecycle recalculations that automatically mark items as `IN_STOCK`, `LOW_STOCK` (when stock <= `minStockLevel`), or `OUT_OF_STOCK` (when stock = 0).
- **Service & Event Layer**: `StockManager` coordinates atomic stock transactions. When a stock movement transitions a product into `LOW_STOCK` or `OUT_OF_STOCK`, it dispatches a `LowStockEvent`.
- **Notification & Webhook Pipeline**: `LowStockSubscriber` listens to `LowStockEvent` and triggers `NotificationService`, sending formatted alert emails and HMAC-SHA256 signed Webhooks (`X-Inventory-Signature`) to external subscribers, while saving audit logs in `notification_logs`.

---

## Testing

Run the full PHPUnit test suite:

```bash
php vendor/phpunit/phpunit/phpunit
```

---

## Data Handling & Privacy

- **Data Collected**: Stores product inventory metadata, category definitions, stock movement logs, webhook subscriber URLs/secrets, and outbound alert delivery logs.
- **Data Persistence**: All records persist locally in configured SQLite database files (`var/app.db`).
- **Secrets & Keys**: Environment variables live in `.env` and are strictly excluded from git version control.

---

## License

Licensed under the [MIT License](LICENSE).
