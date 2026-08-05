# Iteration 8 Summary — Stock Expiration & Lot/Batch Number Tracking

## Plain English Summary
In Iteration 8 of Build 67, we added **Batch/Lot Number Tracking** and a **First Expired, First Out (FEFO)** inventory allocation engine to the Symfony REST API.

Products can now be tracked by manufacturing batch lots (`BatchLot` entity) with explicit manufacturing and expiration dates. When fulfilling orders via `POST /api/v1/batch-lots/fefo-fulfill`, the `BatchLotManager` automatically sorts available batch lots by expiration date and deducts stock starting from the lot nearest to expiration, minimizing product spoilage and waste. Additionally, the `/batch-lots/expiring` endpoint alerts managers to items expiring within a configurable day threshold (e.g. 30 days).

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/BatchLot.php` | Doctrine entity representing manufacturing batch lots with expiration dates | `src/Entity/Product.php`, `src/Repository/BatchLotRepository.php` |
| `src/Repository/BatchLotRepository.php` | Doctrine repository providing FEFO query ordering (`findFefoLots()`) and near-expiration queries | `src/Entity/BatchLot.php` |
| `src/Service/BatchLotManager.php` | Domain service creating batch lots and executing FEFO stock allocation | `src/Service/StockManager.php` |
| `src/Controller/BatchLotController.php` | REST API controller for batch lot creation, FEFO fulfillment, and expiration reports | `GET/POST /api/v1/batch-lots`, `POST /api/v1/batch-lots/fefo-fulfill`, `GET /api/v1/batch-lots/expiring` |
| `tests/Service/BatchLotManagerTest.php` | PHPUnit unit tests for FEFO lot picking order, expiration sorting, and stock allocation | `src/Service/BatchLotManager.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 8 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 8 | `.gitignore` (local only) |
| `docs/summaries/iteration_08_summary.md` | Saved persistent summary for Iteration 8 | Documentation archive |

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
   *(Expected output: `OK (19 tests, 75 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API Batch Lot & FEFO Fulfillment Verification in PowerShell**:
   ```powershell
   # 1. Login as Admin
   $login = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body '{"email": "admin@inventory.internal", "password": "AdminPass123!"}'
   $token = $login.token
   $headers = @{ Authorization = "Bearer $token"; "Content-Type" = "application/json" }

   # 2. Create Batch Lot 1 (Expiring in 10 Days)
   $lot1 = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/batch-lots" -Method Post -Headers $headers -Body '{"product_id": 1, "batch_number": "LOT-EARLY", "quantity": 10, "expiration_date": "2026-08-15"}'
   Write-Host "Created Lot 1 (Early Expire):" ($lot1 | ConvertTo-Json -Compress)

   # 3. Create Batch Lot 2 (Expiring in 60 Days)
   $lot2 = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/batch-lots" -Method Post -Headers $headers -Body '{"product_id": 1, "batch_number": "LOT-LATER", "quantity": 25, "expiration_date": "2026-10-05"}'
   Write-Host "Created Lot 2 (Later Expire):" ($lot2 | ConvertTo-Json -Compress)

   # 4. Fulfill FEFO Order of 15 Units (Should deduct 10 from LOT-EARLY and 5 from LOT-LATER)
   $fefoRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/batch-lots/fefo-fulfill" -Method Post -Headers $headers -Body '{"product_id": 1, "quantity": 15}'
   Write-Host "FEFO Fulfillment Breakdown:" ($fefoRes | ConvertTo-Json -Compress)

   # 5. Check Expiring Lots Report (GET /api/v1/batch-lots/expiring?days=30)
   $expiring = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/batch-lots/expiring?days=30"
   Write-Host "Expiring Lots Report:" ($expiring | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

### Option 1: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, PO statuses, FEFO expiration alerts, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.

### Option 2: API Rate Limiting & Sliding Window Throttle
- **Plain English**: Add sliding window rate limiting (e.g., 60 requests per minute per IP/Token) to protect the API against denial-of-service or brute force attacks.
- **Benefit**: Protects server resources and guarantees API availability under heavy load.
- **Trade-off**: Adds rate limit headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`) and state tracking.
- **Interview Answer**: *"We integrated Symfony RateLimiter with sliding window policies to enforce per-ip and per-user quota controls."*
- **Manual Test Steps**:
  1. Send 61 rapid HTTP requests in a loop.
  2. Verify 61st request receives 429 Too Many Requests response.

### Option 3: Full Multi-Currency Pricing & Tax Rate Matrix
- **Plain English**: Add support for multiple currencies (`USD`, `EUR`, `GBP`, `CAD`) with dynamic exchange rate conversions and tax rate matrices.
- **Benefit**: Essential for international e-commerce and cross-border logistics.
- **Trade-off**: Requires precision currency conversion math and exchange rate updates.
- **Interview Answer**: *"We built a multi-currency pricing engine integrating external exchange rate APIs to convert product prices dynamically based on regional customer locales."*
- **Manual Test Steps**:
  1. Query `GET /api/v1/products?currency=EUR`.
  2. Observe converted prices based on live/mock exchange rates.

### Option 4: Automated Inventory Audit Sampling & Stock Count Reconciliation
- **Plain English**: Generate periodic inventory audit cycles (`AuditCycle` & `AuditDiscrepancy` entities) to compare physical stock counts against system records.
- **Benefit**: Helps identify stock leakage, damage, or theft quickly.
- **Trade-off**: Adds audit workflow state machinery.
- **Interview Answer**: *"We created an inventory audit engine that generates random product sampling lists for physical count reconciliation and logs inventory variance adjustments."*
- **Manual Test Steps**:
  1. Generate audit session via `POST /api/v1/audits`.
  2. Reconcile counted quantities and verify generated `ADJUST` movements.
