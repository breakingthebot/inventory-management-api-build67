# Iteration 1 Summary — Core Foundation, Doctrine ORM Models, Stock Manager, and Full REST CRUD API

## Plain English Summary
In Iteration 1 of Build 67, we designed and implemented a production-ready **Symfony 6.4 Inventory Management REST API** in PHP 8.3 powered by Doctrine ORM and SQLite. 

The API enables complete product lifecycle management, category structuring, and inventory stock tracking. Whenever stock is restocked (`IN`), fulfilled (`OUT`), or audited (`ADJUST`), the system atomically updates the product's stock levels and recalculates its availability status (`IN_STOCK`, `LOW_STOCK`, `OUT_OF_STOCK`). Full transaction histories are recorded in the `stock_movements` table for complete auditability.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `AGENTS.md` | Non-negotiable build standards and project rules for Build 67 | `.gitignore` (excluded from commits) |
| `.gitignore` | Configures git to ignore vendor dependencies, database files, logs, and secrets | Git repository configuration |
| `.env`, `.env.example` | Environment configuration for SQLite database and Symfony secret | `src/Kernel.php`, `config/packages/doctrine.yaml` |
| `composer.json` | Dependency definitions for Symfony 6.4, Doctrine ORM, Validator, Serializer, and PHPUnit | `vendor/autoload.php` |
| `config/bundles.php` | Symfony bundle registry | `src/Kernel.php` |
| `config/packages/framework.yaml` | Symfony framework settings for router, serializer, and validator | `src/Kernel.php` |
| `config/packages/doctrine.yaml` | Doctrine ORM entity mapping and SQLite DBAL configuration | `src/Entity/` |
| `config/routes.yaml` | Attribute route mapping loader | `src/Controller/` |
| `config/services.yaml` | Dependency injection container settings for autowiring services and controllers | `src/Service/`, `src/Controller/` |
| `public/index.php` | Front controller entry point for web server HTTP requests | `src/Kernel.php`, `vendor/autoload_runtime.php` |
| `bin/console` | Symfony CLI application binary for commands and schema migrations | `src/Kernel.php` |
| `src/Kernel.php` | Symfony microkernel bootstrapping configuration and bundles | `public/index.php`, `bin/console` |
| `src/Entity/Category.php` | Doctrine entity for product categories | `src/Entity/Product.php`, `src/Repository/CategoryRepository.php` |
| `src/Entity/Product.php` | Doctrine entity for inventory items with stock status logic | `src/Entity/Category.php`, `src/Entity/StockMovement.php`, `src/Repository/ProductRepository.php` |
| `src/Entity/StockMovement.php` | Doctrine entity logging stock transactions (IN, OUT, ADJUST) | `src/Entity/Product.php`, `src/Repository/StockMovementRepository.php` |
| `src/Repository/CategoryRepository.php` | Doctrine repository for Category queries | `src/Entity/Category.php` |
| `src/Repository/ProductRepository.php` | Doctrine repository for Product queries with search and filter methods | `src/Entity/Product.php` |
| `src/Repository/StockMovementRepository.php` | Doctrine repository for StockMovement logs | `src/Entity/StockMovement.php` |
| `src/Service/StockManager.php` | Business logic service executing atomic stock adjustments and boundary checks | `src/Entity/Product.php`, `src/Entity/StockMovement.php`, `Doctrine\ORM\EntityManagerInterface` |
| `src/Controller/HealthCheckController.php` | REST API health check endpoint | `GET /api/v1/health` |
| `src/Controller/CategoryController.php` | REST API controller for Category CRUD | `GET /api/v1/categories`, `POST /api/v1/categories` |
| `src/Controller/ProductController.php` | REST API controller for Product CRUD with search, filter, and pagination | `GET /api/v1/products`, `POST /api/v1/products`, `PUT /api/v1/products/{id}`, `DELETE /api/v1/products/{id}` |
| `src/Controller/StockMovementController.php` | REST API controller for stock adjustments and audit history | `POST /api/v1/products/{id}/stock`, `GET /api/v1/products/{id}/stock-movements` |
| `tests/bootstrap.php` | PHPUnit bootstrapper setting test environment variables | `phpunit.xml.dist` |
| `phpunit.xml.dist` | PHPUnit configuration file | `tests/` |
| `tests/Service/StockManagerTest.php` | PHPUnit unit tests for StockManager service | `src/Service/StockManager.php` |
| `tests/Controller/ProductControllerTest.php` | PHPUnit unit tests for Product entity status recalculations | `src/Entity/Product.php` |
| `LICENSE` | Standard MIT License | Repo Root |
| `README.md` | Comprehensive project documentation, setup guide, and API reference | Repo Root |
| `CHANGELOG.md` | Keep a Changelog iteration tracker | Repo Root |
| `BUILD_NOTES.md` | Append-only plain English build log | `.gitignore` (local only) |

---

## Manual Testing Steps

To test this pushed iteration manually in another terminal window:

1. **Open a new terminal window** and navigate to the project directory:
   ```bash
   cd C:\Users\marve\Desktop\AI-286-Builds\Build_67
   ```

2. **Ensure PHP 8.3 is in your session environment**:
   ```powershell
   $env:PATH = "C:\php8;" + $env:PATH
   php -v
   ```

3. **Initialize the Database Schema**:
   ```bash
   php bin/console doctrine:schema:create
   ```

4. **Run the PHPUnit Test Suite**:
   ```bash
   php vendor/phpunit/phpunit/phpunit
   ```
   *(Expected output: `OK (7 tests, 23 assertions)`)*

5. **Start the local HTTP server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API verification commands in PowerShell**:
   ```powershell
   # 1. Health Check
   Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/health"

   # 2. Create Category
   Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/categories" -Method Post -ContentType "application/json" -Body '{"name": "Electronics", "description": "Hardware & Devices"}'

   # 3. Create Product
   Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products" -Method Post -ContentType "application/json" -Body '{"sku": "LOGI-MX3S", "name": "Logitech MX Master 3S", "unit_price": 99.99, "stock_quantity": 10, "min_stock_level": 5, "category_id": 1}'

   # 4. Record Stock Deduction (OUT)
   Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1/stock" -Method Post -ContentType "application/json" -Body '{"type": "OUT", "quantity": 6, "reason": "Customer sale", "reference": "SO-1001"}'

   # 5. Fetch Product Detail (Verify stock = 4, status = LOW_STOCK)
   Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1"
   ```

---

## Candidate Next Iterations

### Option 1: Low-Stock Alerts & Email/Webhook Notifications
- **Plain English**: Automatically trigger email notifications or outbound webhooks when a product's stock drops below its `minStockLevel`.
- **Benefit**: Keeps inventory managers and purchasing teams proactively alerted before items sell out completely.
- **Trade-off**: Requires background event dispatching or mailer setup.
- **Interview Answer**: "We implemented Symfony Event Subscribers listening to Doctrine postUpdate events. When stock falls to `LOW_STOCK`, an asynchronous event fires an outbound notification, avoiding synchronous delays on API write paths."
- **Manual Test Steps**:
  1. POST a stock movement reducing item quantity below `minStockLevel`.
  2. Inspect generated notification log / simulated mailer queue.

### Option 2: Multi-Warehouse Location Management
- **Plain English**: Support tracking inventory across multiple physical locations/warehouses (`Warehouse` entity with `WarehouseStock` pivot table).
- **Benefit**: Essential for enterprise supply chains operating regional fulfillment centers.
- **Trade-off**: Increases database schema complexity and requires location parameters on stock movements.
- **Interview Answer**: "We normalized inventory distribution by introducing a `Warehouse` entity and `WarehouseStock` join table, allowing the system to track per-location inventory while maintaining global stock rollups."
- **Manual Test Steps**:
  1. Create warehouses `WH-EAST` and `WH-WEST`.
  2. Transfer 5 units from `WH-EAST` to `WH-WEST` via stock transfer endpoint.

### Option 3: Bulk Import & Export (CSV & Excel)
- **Plain English**: Upload CSV files to bulk import or update products and download full inventory audit reports.
- **Benefit**: Saves warehouse teams hundreds of hours of manual data entry during stock-takes.
- **Trade-off**: Requires streaming response handling and multi-row row validation.
- **Interview Answer**: "We built a stream-based CSV importer with Symfony Validator row parsing, ensuring full batch reporting where invalid rows are isolated without corrupting valid records."
- **Manual Test Steps**:
  1. Upload a CSV file containing 50 products.
  2. Verify JSON summary reporting successful imports and error rows.

### Option 4: JWT Authentication & Role-Based Access Control (RBAC)
- **Plain English**: Secure the API using JWT tokens with granular roles (`ROLE_ADMIN`, `ROLE_WAREHOUSE_WORKER`, `ROLE_AUDITOR`).
- **Benefit**: Enforces security compliance so only authorized staff can adjust stock levels.
- **Trade-off**: Adds authentication header requirements to all test suites.
- **Interview Answer**: "We integrated LexikJWTAuthenticationBundle and Symfony Security Voters to enforce role-based authorization rules across stock write operations."
- **Manual Test Steps**:
  1. Request JWT token via `/api/v1/login`.
  2. Perform POST with `Authorization: Bearer <token>` and verify 201 Created.
