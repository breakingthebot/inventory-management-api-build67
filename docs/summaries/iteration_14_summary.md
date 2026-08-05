# Iteration 14 Summary — Stock Reservation Engine for E-Commerce Orders

## Plain English Summary
In Iteration 14 of Build 67, we built a **Stock Reservation Engine for E-Commerce Checkouts** (`StockReservationEngine` service, `StockReservation` entity).

During high-traffic shopping cart checkouts or flash sales, customer checkouts create temporary stock holds (`POST /api/v1/reservations`) with a TTL expiration (default 15 minutes). The system calculates available unreserved stock ($Available = PhysicalStock - HeldReservations$) and rejects reservation attempts that exceed available quantity. When the user completes payment (`POST /api/v1/reservations/{token}/confirm`), the hold is marked `CONFIRMED` and physical stock is deducted via `StockManager`. If the checkout is abandoned or TTL expires, the hold is released automatically.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/StockReservation.php` | Doctrine entity representing temporary e-commerce cart stock holds with TTL expiration | `src/Entity/Product.php`, `src/Repository/StockReservationRepository.php` |
| `src/Repository/StockReservationRepository.php` | Doctrine repository providing reserved quantity sums and TTL cleanup queries | `src/Entity/StockReservation.php` |
| `src/Service/StockReservationEngine.php` | Domain service calculating available unreserved stock, holding cart reservations, and confirming checkouts | `src/Service/StockManager.php` |
| `src/Controller/StockReservationController.php` | REST API controller for reserving stock, confirming orders, and releasing held reservations | `POST /api/v1/reservations`, `GET /api/v1/reservations/{token}`, `POST /api/v1/reservations/{token}/confirm`, `POST /api/v1/reservations/{token}/cancel` |
| `tests/Service/StockReservationEngineTest.php` | PHPUnit unit tests for unreserved stock calculations, checkout holds, and confirming orders | `src/Service/StockReservationEngine.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 14 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 14 | `.gitignore` (local only) |
| `docs/summaries/iteration_14_summary.md` | Saved persistent summary for Iteration 14 | Documentation archive |

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
   *(Expected output: `OK (30 tests, 129 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP Verification in PowerShell**:
   ```powershell
   # 1. Reserve 5 Units of Product 1 for 15 Minutes (POST /api/v1/reservations)
   $res = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/reservations" -Method Post -ContentType "application/json" -Body '{"product_id": 1, "quantity": 5, "ttl_minutes": 15}'
   $token = $res.reservationToken
   Write-Host "Reservation Created Token:" $token "Expires At:" $res.expiresAt

   # 2. Inspect Reservation Status (GET /api/v1/reservations/{token})
   $status = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/reservations/$token"
   Write-Host "Reservation Status:" ($status | ConvertTo-Json -Compress)

   # 3. Confirm Order Checkout & Deduct Physical Inventory (POST /api/v1/reservations/{token}/confirm)
   $confirmRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/reservations/$token/confirm" -Method Post
   Write-Host "Confirmed Reservation Result:" ($confirmRes | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

Please choose one of the following candidate iterations to continue:

### Option 1: Full Multi-Tenant Account & Organization Isolation
- **Plain English**: Support multi-tenant business organizations (`Tenant` entity) with strict data isolation across categories, products, warehouses, and orders.
- **Benefit**: Allows multiple independent companies to run on a single API instance.
- **Trade-off**: Adds tenant filter listeners across all database queries.
- **Interview Answer**: *"We implemented multi-tenancy using Doctrine SQL filters to isolate database queries automatically by tenant organization ID."*

### Option 2: Custom Export Report Builder (PDF & Excel XML)
- **Plain English**: Generate styled PDF inventory valuation certificates and Excel XML stock audit reports for corporate accounting.
- **Benefit**: Essential for legal compliance and formal business reporting.
- **Trade-off**: Requires PDF document layout generation.
- **Interview Answer**: *"We built a PDF export service generating official valuation statements and stock balance sheets."*

### Option 3: Product Variant Matrix & SKU Options Engine
- **Plain English**: Support product color, size, and material option matrices (`ProductOption` & `ProductVariant` entities) with individual SKU stock tracking.
- **Benefit**: Essential for fashion, apparel, and hardware inventory control.
- **Trade-off**: Adds parent-child variant relationship hierarchy.
- **Interview Answer**: *"We modeled apparel and hardware product options with parent-child variant hierarchies and individual SKU stock levels."*

### Option 4: Automated Backorder Queue & Allocation Engine
- **Plain English**: Allow customers to place backorders (`Backorder` entity) when items are `OUT_OF_STOCK` and automatically allocate incoming supplier shipments to backordered customers in FIFO queue order.
- **Benefit**: Captures sales demand even during temporary stockout windows.
- **Trade-off**: Adds FIFO priority queue processing.
- **Interview Answer**: *"We implemented a FIFO backorder queue engine that automatically fulfills backordered customer queues upon receiving supplier shipment shipments."*

---

Please let me know which candidate iteration you would like to proceed with!
