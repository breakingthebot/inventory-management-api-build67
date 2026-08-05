# Iteration 3 Summary — Multi-Warehouse Location Management

## Plain English Summary
In Iteration 3 of Build 67, we expanded the API to support **Multi-Warehouse Location Management**.

Businesses can now create separate physical fulfillment centers (e.g. `WH-EAST`, `WH-WEST`) and track inventory quantities on a per-location basis (`warehouse_stocks`). The `WarehouseManager` domain service handles location-specific stock adjustments, executes inter-warehouse stock transfers (`transferStock()`), and automatically syncs local warehouse quantities to the product's global total stock and operational status.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/Warehouse.php` | Doctrine entity for physical warehouse locations | `src/Entity/WarehouseStock.php`, `src/Repository/WarehouseRepository.php` |
| `src/Repository/WarehouseRepository.php` | Doctrine repository for Warehouse queries | `src/Entity/Warehouse.php` |
| `src/Entity/WarehouseStock.php` | Doctrine entity tracking per-location stock levels | `src/Entity/Warehouse.php`, `src/Entity/Product.php` |
| `src/Repository/WarehouseStockRepository.php` | Doctrine repository for WarehouseStock queries and global stock sums | `src/Entity/WarehouseStock.php` |
| `src/Entity/StockMovement.php` | Updated with optional `warehouse` relationship and `TRANSFER` type | `src/Entity/Warehouse.php` |
| `src/Service/WarehouseManager.php` | Domain service handling location stock adjustments, transfers, and global rollups | `src/Entity/WarehouseStock.php`, `src/Entity/Product.php` |
| `src/Controller/WarehouseController.php` | REST API controller for Warehouse CRUD, stock adjustments, and inter-warehouse transfers | `POST/GET /api/v1/warehouses`, `POST /api/v1/warehouses/{id}/stock`, `POST /api/v1/warehouses/transfer` |
| `tests/Service/WarehouseManagerTest.php` | PHPUnit unit tests for WarehouseManager allocations, transfers, and boundary errors | `src/Service/WarehouseManager.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 3 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 3 | `.gitignore` (local only) |
| `docs/summaries/iteration_03_summary.md` | Saved persistent summary for Iteration 3 | Documentation archive |

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

3. **Update Database Schema**:
   ```bash
   php bin/console doctrine:schema:update --force
   ```

4. **Run PHPUnit Test Suite**:
   ```bash
   php vendor/phpunit/phpunit/phpunit
   ```
   *(Expected output: `OK (12 tests, 39 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API Multi-Warehouse Verification in PowerShell**:
   ```powershell
   # 1. Create East Coast Warehouse
   $whEast = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/warehouses" -Method Post -ContentType "application/json" -Body '{"code": "WH-EAST", "name": "East Coast Fulfillment Hub", "address": "100 Logistics Way, NJ"}'
   Write-Host "Created East Warehouse:" ($whEast | ConvertTo-Json -Compress)

   # 2. Create West Coast Warehouse
   $whWest = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/warehouses" -Method Post -ContentType "application/json" -Body '{"code": "WH-WEST", "name": "West Coast Fulfillment Hub", "address": "500 Cargo Blvd, CA"}'
   Write-Host "Created West Warehouse:" ($whWest | ConvertTo-Json -Compress)

   # 3. Add Stock to WH-EAST for Product 1
   $eastStock = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/warehouses/1/stock" -Method Post -ContentType "application/json" -Body '{"product_id": 1, "type": "IN", "quantity": 50, "reason": "Initial stock allocation"}'
   Write-Host "East Stock Added:" ($eastStock | ConvertTo-Json -Compress)

   # 4. Transfer 20 Units from WH-EAST to WH-WEST
   $transfer = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/warehouses/transfer" -Method Post -ContentType "application/json" -Body '{"source_warehouse_id": 1, "target_warehouse_id": 2, "product_id": 1, "quantity": 20, "reference": "TR-1001"}'
   Write-Host "Transfer Result:" ($transfer | ConvertTo-Json -Compress)

   # 5. Get Detail of WH-EAST (Verify 30 units remaining)
   $eastDetail = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/warehouses/1"
   Write-Host "WH-EAST Detail:" ($eastDetail | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

### Option 1: Bulk Import & Export Utility (CSV Format)
- **Plain English**: Upload CSV spreadsheets to bulk create/update products and download full CSV stock reports.
- **Benefit**: Saves warehouse staff hundreds of hours of manual entry during periodic stock-takes.
- **Trade-off**: Requires stream parsing and multi-row validation error aggregation.
- **Interview Answer**: *"We built a stream-based CSV importer with Symfony Validator row parsing, ensuring full batch accounting where bad rows are reported cleanly without corrupting valid records."*
- **Manual Test Steps**:
  1. Upload a CSV file containing 50 products.
  2. Verify JSON summary reporting successful imports and error rows.

### Option 2: LexikJWT Authentication & Role-Based Access Control (RBAC)
- **Plain English**: Secure write endpoints with JWT tokens and user roles (`ROLE_ADMIN`, `ROLE_WAREHOUSE`, `ROLE_AUDITOR`).
- **Benefit**: Restricts stock adjustments and deletion capabilities to authorized personnel.
- **Trade-off**: Requires passing Bearer tokens in headers across all integration tests.
- **Interview Answer**: *"We integrated LexikJWTAuthenticationBundle and Symfony Security Voters to enforce role-based authorization across stock write operations."*
- **Manual Test Steps**:
  1. Request JWT token via `/api/v1/login`.
  2. Perform POST with `Authorization: Bearer <token>` and verify 201 Created.

### Option 3: Automated Purchase Order Reordering System
- **Plain English**: Automatically generate draft Purchase Orders (`PurchaseOrder` & `POLineItem` entities) when items drop to `LOW_STOCK`.
- **Benefit**: Automates re-ordering from suppliers to prevent stockout delays.
- **Trade-off**: Introduces Supplier relationships and PO state machine logic.
- **Interview Answer**: *"We connected the `LowStockEvent` pipeline to an automated PO generation engine that calculates optimal reorder quantities based on supplier lead times."*
- **Manual Test Steps**:
  1. Trigger low stock event for an item.
  2. Query `GET /api/v1/purchase-orders` to verify generated draft PO.

### Option 4: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.
