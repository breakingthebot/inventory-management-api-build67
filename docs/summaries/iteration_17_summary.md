# Iteration 17 Summary — Product Variant Matrix & SKU Options Engine

## Plain English Summary
In Iteration 17 of Build 67, we added a **Product Variant Matrix & SKU Options Engine** (`VariantManager` service, `ProductOption` & `ProductVariant` entities).

Complex catalog products (such as apparel, electronics, or hardware) can now define child variant SKUs (`ProductVariant`) under a parent product (e.g. `SHIRT-BASE` -> `SHIRT-RED-XL`, `SHIRT-BLUE-M`). Each variant SKU maintains its own option value mappings (`{"color":"Red", "size":"XL"}`), price overrides, stock quantities, and minimum stock threshold alerts.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/ProductOption.php` | Doctrine entity representing configurable product attribute options (Color, Size, Material) | `src/Entity/ProductVariant.php` |
| `src/Repository/ProductOptionRepository.php` | Doctrine repository for ProductOption entities | `src/Entity/ProductOption.php` |
| `src/Entity/ProductVariant.php` | Doctrine entity representing child SKU variants under a parent catalog product | `src/Entity/Product.php` |
| `src/Repository/ProductVariantRepository.php` | Doctrine repository for ProductVariant entities | `src/Entity/ProductVariant.php` |
| `src/Service/VariantManager.php` | Domain service managing variant matrix generation, price overrides, and variant stock tracking | `src/Entity/ProductVariant.php` |
| `src/Entity/Product.php` | Updated with `variants` OneToMany relationship | `src/Entity/ProductVariant.php` |
| `src/Controller/ProductVariantController.php` | REST API controller for managing child variants and variant stock levels | `GET/POST /api/v1/products/{id}/variants`, `POST /api/v1/variants/{id}/stock` |
| `tests/Service/VariantManagerTest.php` | PHPUnit unit tests for variant SKU generation, option mapping, price overrides, and stock adjustments | `src/Service/VariantManager.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 17 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 17 | `.gitignore` (local only) |
| `docs/summaries/iteration_17_summary.md` | Saved persistent summary for Iteration 17 | Documentation archive |

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
   *(Expected output: `OK (38 tests, 152 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP Verification in PowerShell**:
   ```powershell
   # 1. Login as Admin
   $login = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body '{"email": "admin@inventory.internal", "password": "AdminPass123!"}'
   $token = $login.token
   $headers = @{ Authorization = "Bearer $token"; "Content-Type" = "application/json" }

   # 2. Create Child Variant SKU under Parent Product 1 (POST /api/v1/products/1/variants)
   $variant = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1/variants" -Method Post -Headers $headers -Body '{"sku": "PROD1-RED-XL", "option_values": {"color": "Red", "size": "XL"}, "price_override": 49.99, "stock_quantity": 25}'
   Write-Host "Created Variant SKU:" ($variant | ConvertTo-Json -Compress)

   # 3. List Variants for Parent Product 1 (GET /api/v1/products/1/variants)
   $variantsList = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1/variants"
   Write-Host "Variants List Output:" ($variantsList | ConvertTo-Json -Compress)

   # 4. Adjust Variant Stock Level (POST /api/v1/variants/1/stock)
   $adjRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/variants/1/stock" -Method Post -Headers $headers -Body '{"quantity": 10, "type": "OUT"}'
   Write-Host "Adjusted Variant Stock:" ($adjRes | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

Please choose one of the following candidate iterations to continue:

### Option 1: Automated Backorder Queue & Allocation Engine
- **Plain English**: Allow customers to place backorders (`Backorder` entity) when items are `OUT_OF_STOCK` and automatically allocate incoming supplier shipments to backordered customers in FIFO queue order.
- **Benefit**: Captures sales demand even during temporary stockout windows.
- **Trade-off**: Adds FIFO priority queue processing.
- **Interview Answer**: *"We implemented a FIFO backorder queue engine that automatically fulfills backordered customer queues upon receiving supplier shipment shipments."*

### Option 2: Full Audit Trail Event Sourcing & Revision History
- **Plain English**: Track deep revision histories (`EntityRevision` entity) across catalog updates with rollback capabilities.
- **Benefit**: Enables enterprise compliance auditing and instant rollback of unintended entity edits.
- **Trade-off**: Increases database storage requirements.
- **Interview Answer**: *"We built an event-sourced entity revision auditing subsystem allowing historical state inspections and point-in-time rollbacks."*

### Option 3: Real-time SSE (Server-Sent Events) Stock Stream
- **Plain English**: Stream live inventory stock updates to connected frontend clients using HTML5 Server-Sent Events (`/api/v1/events/stock-stream`).
- **Benefit**: Enables real-time reactive warehouse dashboards without polling overhead.
- **Trade-off**: Requires persistent streaming HTTP connections.
- **Interview Answer**: *"We implemented Server-Sent Events (SSE) streaming real-time inventory adjustments directly to admin dashboards."*

### Option 4: Automated Supplier Performance & Lead Time Analytics Engine
- **Plain English**: Track supplier lead times, delivery fulfillment accuracy percentages, and defective shipment logs (`SupplierMetrics` entity).
- **Benefit**: Provides data-driven vendor scorecards for supply chain optimization.
- **Trade-off**: Adds vendor performance aggregation background metrics.
- **Interview Answer**: *"We built a supplier scorecard engine evaluating lead-time latency, on-time delivery rates, and vendor fulfillment accuracy."*

---

Please let me know which candidate iteration you would like to proceed with!
