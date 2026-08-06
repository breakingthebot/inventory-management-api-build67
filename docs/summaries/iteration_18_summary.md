# Iteration 18 Summary — Automated Backorder Queue & Allocation Engine

## Plain English Summary
In Iteration 18 of Build 67, we built an **Automated Backorder Queue & Allocation Engine** (`BackorderManager` service, `Backorder` entity).

When items are `OUT_OF_STOCK`, customers or sales managers can place backorders (`POST /api/v1/backorders`). Backorder entries are held in a strict First-In, First-Out (FIFO) queue (`createdAt ASC`). Whenever new inventory arrives (e.g. receiving a supplier Purchase Order shipment), `BackorderManager::allocateStockToBackorders()` processes the queue, fulfills pending backorders, records `TYPE_OUT` stock transactions, and marks backorder status to `FULFILLED`.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/Backorder.php` | Doctrine entity representing customer backorder requests queued in FIFO order | `src/Entity/Product.php`, `src/Repository/BackorderRepository.php` |
| `src/Repository/BackorderRepository.php` | Doctrine repository providing FIFO queue queries (`findPendingBackordersForProduct()`) | `src/Entity/Backorder.php` |
| `src/Service/BackorderManager.php` | Domain service managing customer backorder queues and FIFO stock fulfillment allocations | `src/Service/StockManager.php` |
| `src/Controller/BackorderController.php` | REST API controller for placing backorders, listing the FIFO queue, and cancelling backorders | `GET/POST /api/v1/backorders`, `POST /api/v1/backorders/{id}/cancel` |
| `tests/Service/BackorderManagerTest.php` | PHPUnit unit tests for backorder creation, FIFO allocation ordering, and status transitions | `src/Service/BackorderManager.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 18 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 18 | `.gitignore` (local only) |
| `docs/summaries/iteration_18_summary.md` | Saved persistent summary for Iteration 18 | Documentation archive |

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
   *(Expected output: `OK (40 tests, 163 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP Verification in PowerShell**:
   ```powershell
   # 1. Place Backorder Request for Product 1 (POST /api/v1/backorders)
   $bo = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/backorders" -Method Post -ContentType "application/json" -Body '{"product_id": 1, "customer_email": "vip-client@company.internal", "quantity": 3}'
   Write-Host "Created Backorder Number:" $bo.backorderNumber "Status:" $bo.status

   # 2. List Pending Backorder Queue (GET /api/v1/backorders?status=PENDING)
   $queue = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/backorders?status=PENDING"
   Write-Host "Pending Backorders Count:" $queue.Count

   # 3. Cancel Backorder Request (POST /api/v1/backorders/1/cancel)
   $cancelRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/backorders/$($bo.id)/cancel" -Method Post
   Write-Host "Cancelled Backorder Result:" ($cancelRes | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

Please choose one of the following candidate iterations to continue:

### Option 1: Full Audit Trail Event Sourcing & Revision History
- **Plain English**: Track deep revision histories (`EntityRevision` entity) across catalog updates with rollback capabilities.
- **Benefit**: Enables enterprise compliance auditing and instant rollback of unintended entity edits.
- **Trade-off**: Increases database storage requirements.
- **Interview Answer**: *"We built an event-sourced entity revision auditing subsystem allowing historical state inspections and point-in-time rollbacks."*

### Option 2: Real-time SSE (Server-Sent Events) Stock Stream
- **Plain English**: Stream live inventory stock updates to connected frontend clients using HTML5 Server-Sent Events (`/api/v1/events/stock-stream`).
- **Benefit**: Enables real-time reactive warehouse dashboards without polling overhead.
- **Trade-off**: Requires persistent streaming HTTP connections.
- **Interview Answer**: *"We implemented Server-Sent Events (SSE) streaming real-time inventory adjustments directly to admin dashboards."*

### Option 3: Automated Supplier Performance & Lead Time Analytics Engine
- **Plain English**: Track supplier lead times, delivery fulfillment accuracy percentages, and defective shipment logs (`SupplierMetrics` entity).
- **Benefit**: Provides data-driven vendor scorecards for supply chain optimization.
- **Trade-off**: Adds vendor performance aggregation background metrics.
- **Interview Answer**: *"We built a supplier scorecard engine evaluating lead-time latency, on-time delivery rates, and vendor fulfillment accuracy."*

### Option 4: Automated Inventory ABC Classification & Cycle Analysis
- **Plain English**: Classify catalog inventory items into Category A (High Value/Fast Moving), Category B (Moderate), and Category C (Low Value/Slow Moving) using Pareto analysis ($80/20$ rule).
- **Benefit**: Optimizes warehouse slotting and priority count frequencies.
- **Trade-off**: Requires periodic ABC classification calculation jobs.
- **Interview Answer**: *"We implemented automated Pareto ABC classification scoring catalog inventory to optimize warehouse picking layouts and audit frequencies."*

---

Please let me know which candidate iteration you would like to proceed with!
