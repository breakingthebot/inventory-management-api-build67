# Iteration 4 Summary — Bulk Import & Export Utility (CSV Format)

## Plain English Summary
In Iteration 4 of Build 67, we added a streaming **CSV Bulk Import & Export Subsystem** to the Symfony Inventory Management API.

Warehouse and inventory managers can now upload large CSV spreadsheets to bulk create new products or update existing inventory items by SKU match (`POST /api/v1/products/import/csv`). The `CsvBatchImporter` validates each row using Symfony Validator, auto-provisions categories, and collects row-level error reports without crashing or aborting the batch. Additionally, stream-based CSV export endpoints (`/export/csv`) allow instant downloads of product archives and stock movement audit logs.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Service/CsvBatchImporter.php` | Service parsing CSV streams, validating rows, aggregating errors, and updating products | `src/Entity/Product.php`, `src/Entity/Category.php` |
| `src/Service/CsvExporter.php` | Service generating streaming CSV downloads for products and stock movement audit logs | `src/Repository/ProductRepository.php`, `src/Repository/StockMovementRepository.php` |
| `src/Controller/ImportExportController.php` | REST API controller for CSV bulk imports and exports | `POST /api/v1/products/import/csv`, `GET /api/v1/products/export/csv`, `GET /api/v1/stock-movements/export/csv` |
| `tests/Service/CsvBatchImporterTest.php` | PHPUnit unit tests for CSV import parsing, SKU updates, and Chaos Fixture error reporting | `src/Service/CsvBatchImporter.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 4 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 4 | `.gitignore` (local only) |
| `docs/summaries/iteration_04_summary.md` | Saved persistent summary for Iteration 4 | Documentation archive |

---

## Manual Testing Steps

To test this pushed iteration manually in another terminal window:

1. **Open a terminal window** and navigate to the project directory:
   ```bash
   cd C:\Users\marve\Desktop\AI-286-Builds\Build_67
   ```

2. **Ensure PHP 8.3 is in your session environment**:
   ```powershell
   $env:PATH = "C:\php8;" + $env:PATH
   php -v
   ```

3. **Run PHPUnit Test Suite**:
   ```bash
   php vendor/phpunit/phpunit/phpunit
   ```
   *(Expected output: `OK (14 tests, 53 assertions)`)*

4. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

5. **Execute HTTP API CSV Bulk Import & Export Verification in PowerShell**:
   ```powershell
   # 1. Prepare sample CSV Payload string
   $csvContent = @"
   sku,name,description,unit_price,stock_quantity,min_stock_level,category
   KEY-100,Wireless Mechanical Keyboard,RGB Hot-swappable keyboard,129.99,30,5,Peripherals
   DISP-200,4K Gaming Monitor,27 inch 144Hz IPS display,349.50,15,3,Monitors
   HEAD-300,Noise-Canceling Headset,Wireless Bluetooth 5.2,199.00,20,4,Audio
   "@

   # 2. Upload CSV Bulk Import
   $importResult = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/import/csv" -Method Post -ContentType "text/csv" -Body $csvContent
   Write-Host "CSV Import Summary:" ($importResult | ConvertTo-Json -Compress)

   # 3. Test Product CSV Export Endpoint
   $exportedProducts = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/export/csv" -Method Get
   Write-Host "Exported Products CSV Output:`n" $exportedProducts

   # 4. Test Stock Movements CSV Export Endpoint
   $exportedMovements = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/stock-movements/export/csv" -Method Get
   Write-Host "Exported Stock Movements CSV Output:`n" $exportedMovements
   ```

---

## Candidate Next Iterations

### Option 1: LexikJWT Authentication & Role-Based Access Control (RBAC)
- **Plain English**: Secure write endpoints with JWT tokens and user roles (`ROLE_ADMIN`, `ROLE_WAREHOUSE`, `ROLE_AUDITOR`).
- **Benefit**: Restricts stock adjustments, imports, and deletion capabilities to authorized personnel.
- **Trade-off**: Requires passing Bearer tokens in headers across all integration tests.
- **Interview Answer**: *"We integrated LexikJWTAuthenticationBundle and Symfony Security Voters to enforce role-based authorization across stock write operations."*
- **Manual Test Steps**:
  1. Request JWT token via `/api/v1/login`.
  2. Perform POST with `Authorization: Bearer <token>` and verify 201 Created.

### Option 2: Automated Purchase Order Reordering System
- **Plain English**: Automatically generate draft Purchase Orders (`PurchaseOrder` & `POLineItem` entities) when items drop to `LOW_STOCK`.
- **Benefit**: Automates re-ordering from suppliers to prevent stockout delays.
- **Trade-off**: Introduces Supplier relationships and PO state machine logic.
- **Interview Answer**: *"We connected the `LowStockEvent` pipeline to an automated PO generation engine that calculates optimal reorder quantities based on supplier lead times."*
- **Manual Test Steps**:
  1. Trigger low stock event for an item.
  2. Query `GET /api/v1/purchase-orders` to verify generated draft PO.

### Option 3: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.

### Option 4: GitHub Actions CI Workflow Setup
- **Plain English**: Add automated GitHub Actions CI pipeline running PHPUnit tests, Doctrine schema validation, and linting on every git push.
- **Benefit**: Guarantees codebase health and prevents broken code from being merged.
- **Trade-off**: Requires configuring YAML workflow files in `.github/workflows/ci.yml`.
- **Interview Answer**: *"We configured a GitHub Actions CI matrix running PHPUnit test suites and Doctrine schema validation checks against SQLite databases on every pull request."*
- **Manual Test Steps**:
  1. Inspect `.github/workflows/ci.yml`.
  2. Trigger git push and view GitHub Actions green checkmark.
