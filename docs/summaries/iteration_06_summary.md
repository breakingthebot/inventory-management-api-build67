# Iteration 6 Summary — Automated Purchase Order Reordering System

## Plain English Summary
In Iteration 6 of Build 67, we added an **Automated Purchase Order (PO) Reordering & Goods Receiving Pipeline** to the Symfony Inventory Management API.

When low-stock events occur (`LOW_STOCK` or `OUT_OF_STOCK`), `ReorderEventSubscriber` triggers `PurchaseOrderGenerator`. The generator calculates optimal reorder quantities based on target thresholds (`(minStockLevel * 2) - currentStock`) and automatically creates or appends line items to `DRAFT` Purchase Orders for vendor suppliers. When shipment containers arrive, calling `POST /api/v1/purchase-orders/{id}/receive` marks the PO as `RECEIVED` and automatically injects the received stock into inventory.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/Supplier.php` | Doctrine entity for vendor suppliers | `src/Entity/PurchaseOrder.php`, `src/Repository/SupplierRepository.php` |
| `src/Repository/SupplierRepository.php` | Doctrine repository for Supplier entities | `src/Entity/Supplier.php` |
| `src/Entity/PurchaseOrder.php` | Doctrine entity managing Purchase Orders and state transitions | `src/Entity/Supplier.php`, `src/Entity/PurchaseOrderItem.php` |
| `src/Entity/PurchaseOrderItem.php` | Doctrine entity representing PO line items, quantities ordered/received, and unit costs | `src/Entity/PurchaseOrder.php`, `src/Entity/Product.php` |
| `src/Repository/PurchaseOrderRepository.php` | Doctrine repository for PurchaseOrder queries | `src/Entity/PurchaseOrder.php` |
| `src/Repository/PurchaseOrderItemRepository.php` | Doctrine repository for PurchaseOrderItem line items | `src/Entity/PurchaseOrderItem.php` |
| `src/Service/PurchaseOrderGenerator.php` | Domain service calculating reorder quantities, generating draft POs, and receiving shipments | `src/Service/StockManager.php`, `src/Service/WarehouseManager.php` |
| `src/EventSubscriber/ReorderEventSubscriber.php` | EventSubscriber triggering PO reorders on `LowStockEvent` | `src/Event/LowStockEvent.php`, `src/Service/PurchaseOrderGenerator.php` |
| `src/Controller/SupplierController.php` | REST API controller for managing vendor suppliers | `GET/POST /api/v1/suppliers` |
| `src/Controller/PurchaseOrderController.php` | REST API controller for listing POs, fetching details, and receiving shipments | `GET /api/v1/purchase-orders`, `POST /api/v1/purchase-orders/{id}/receive` |
| `tests/Service/PurchaseOrderGeneratorTest.php` | PHPUnit unit tests for reorder formula calculations, draft PO creation, and receiving goods | `src/Service/PurchaseOrderGenerator.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 6 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 6 | `.gitignore` (local only) |
| `docs/summaries/iteration_06_summary.md` | Saved persistent summary for Iteration 6 | Documentation archive |

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
   *(Expected output: `OK (18 tests, 67 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API Purchase Order Reorder Verification in PowerShell**:
   ```powershell
   # 1. Login as Admin
   $login = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body '{"email": "admin@inventory.internal", "password": "AdminPass123!"}'
   $token = $login.token

   # 2. Trigger Low-Stock Event on Product 1 (Deduct stock below min_stock_level)
   $headers = @{ Authorization = "Bearer $token"; "Content-Type" = "application/json" }
   $stk = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1/stock" -Method Post -Headers $headers -Body '{"type": "OUT", "quantity": 8, "reason": "Customer sale", "reference": "SO-3003"}'
   Write-Host "Stock Deducted:" ($stk | ConvertTo-Json -Compress)

   # 3. Query Generated Draft Purchase Orders (GET /api/v1/purchase-orders?status=DRAFT)
   $poList = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/purchase-orders?status=DRAFT" -Headers @{ Authorization = "Bearer $token" }
   Write-Host "Generated Draft Purchase Orders:" ($poList | ConvertTo-Json -Compress)

   # 4. Receive Goods Shipment for PO #1 (POST /api/v1/purchase-orders/1/receive)
   $receiveRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/purchase-orders/1/receive" -Method Post -Headers $headers
   Write-Host "Received Shipment PO Result:" ($receiveRes | ConvertTo-Json -Compress)

   # 5. Verify Product Stock Restocked (GET /api/v1/products/1)
   $prod = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1"
   Write-Host "Restocked Product Detail (Verify stock replenished):" ($prod | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

### Option 1: GitHub Actions CI Workflow Setup
- **Plain English**: Add automated GitHub Actions CI pipeline running PHPUnit tests, Doctrine schema validation, and linting on every git push.
- **Benefit**: Guarantees codebase health and prevents broken code from being merged.
- **Trade-off**: Requires configuring YAML workflow files in `.github/workflows/ci.yml`.
- **Interview Answer**: *"We configured a GitHub Actions CI matrix running PHPUnit test suites and Doctrine schema validation checks against SQLite databases on every pull request."*
- **Manual Test Steps**:
  1. Inspect `.github/workflows/ci.yml`.
  2. Trigger git push and view GitHub Actions green checkmark.

### Option 2: Stock Expiration & Lot/Batch Number Tracking
- **Plain English**: Support batch numbers and expiration dates for perishable items (`BatchLot` entity with expiration alerts).
- **Benefit**: Essential for food, beverage, pharmaceutical, and chemical inventory management.
- **Trade-off**: Requires tracking FEFO (First Expired, First Out) picking logic.
- **Interview Answer**: *"We introduced a `BatchLot` entity tracking manufacturing dates and expiration windows, enforcing FEFO inventory allocation during sales fulfillment."*
- **Manual Test Steps**:
  1. Create product with 2 batch lots having different expiration dates.
  2. Deduct stock and verify FEFO picking assigns stock from the earliest expiring lot.

### Option 3: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, PO statuses, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.

### Option 4: API Rate Limiting & Sliding Window Throttle
- **Plain English**: Add sliding window rate limiting (e.g., 60 requests per minute per IP/Token) to protect the API against denial-of-service or brute force attacks.
- **Benefit**: Protects server resources and guarantees API availability.
- **Trade-off**: Adds rate limit headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`) and state tracking.
- **Interview Answer**: *"We integrated Symfony RateLimiter with sliding window policies to enforce per-ip and per-user quota controls."*
- **Manual Test Steps**:
  1. Send 61 rapid HTTP requests in a loop.
  2. Verify 61st request receives 429 Too Many Requests response.
