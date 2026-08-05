# Iteration 5 Summary — Authentication & Role-Based Access Control (RBAC)

## Plain English Summary
In Iteration 5 of Build 67, we integrated **User Authentication & Role-Based Access Control (RBAC)** into the Symfony Inventory Management REST API.

Users can authenticate via `POST /api/v1/auth/login` to obtain an HMAC-signed Bearer Token. The API enforces three granular user roles:
- **`ROLE_ADMIN`**: Full administrative access (create/update/delete products, manage categories, warehouses, and webhook subscriptions).
- **`ROLE_WAREHOUSE`**: Operations staff authorized to adjust stock levels, execute inter-warehouse transfers, update product attributes, and run CSV bulk imports.
- **`ROLE_VIEWER`**: Read-only access across all GET endpoints.

Write operations now require a valid `Authorization: Bearer <token>` header, returning `401 Unauthorized` for unauthenticated requests and `403 Forbidden` for insufficient role permissions.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Entity/User.php` | Doctrine entity implementing `UserInterface` & `PasswordAuthenticatedUserInterface` | `src/Repository/UserRepository.php` |
| `src/Repository/UserRepository.php` | Doctrine repository for User entities | `src/Entity/User.php` |
| `src/Service/TokenAuthenticator.php` | Service issuing and verifying HMAC-SHA256 signed Bearer tokens | `src/Entity/User.php`, `src/Controller/AuthController.php` |
| `src/Controller/AuthController.php` | REST API controller for user authentication and profile inspection with account seeder | `POST /api/v1/auth/login`, `GET /api/v1/auth/me` |
| `src/Controller/ProductController.php` | Updated to enforce Bearer token authentication and role permissions | `src/Service/TokenAuthenticator.php` |
| `tests/Controller/AuthControllerTest.php` | PHPUnit unit tests for token issuance, signature verification, and RBAC rules | `src/Service/TokenAuthenticator.php` |
| `README.md` | Updated API reference, seeded user credentials, and security architecture | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 5 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 5 | `.gitignore` (local only) |
| `docs/summaries/iteration_05_summary.md` | Saved persistent summary for Iteration 5 | Documentation archive |

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
   *(Expected output: `OK (16 tests, 58 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API Authentication & RBAC Verification in PowerShell**:
   ```powershell
   # 1. Login as Admin
   $loginRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body '{"email": "admin@inventory.internal", "password": "AdminPass123!"}'
   $token = $loginRes.token
   Write-Host "Admin Token Issued:" $token

   # 2. Check Auth Profile (GET /api/v1/auth/me)
   $me = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/me" -Headers @{ Authorization = "Bearer $token" }
   Write-Host "Authenticated Profile:" ($me | ConvertTo-Json -Compress)

   # 3. Create Product with Admin Bearer Token
   $headers = @{ Authorization = "Bearer $token"; "Content-Type" = "application/json" }
   $newProd = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products" -Method Post -Headers $headers -Body '{"sku": "AUTH-PROD1", "name": "Secure Item", "unit_price": 49.99, "stock_quantity": 20}'
   Write-Host "Protected Product Created:" ($newProd | ConvertTo-Json -Compress)

   # 4. Login as Viewer (Read-only auditor)
   $viewerLogin = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body '{"email": "auditor@inventory.internal", "password": "AuditorPass123!"}'
   $viewerToken = $viewerLogin.token

   # 5. Attempt Delete as Viewer (Expect 403 Forbidden Error)
   try {
       Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1" -Method Delete -Headers @{ Authorization = "Bearer $viewerToken" }
   } catch {
       Write-Host "Viewer Delete Attempt Blocked (Expected 403):" $_.Exception.Message
   }
   ```

---

## Candidate Next Iterations

### Option 1: Automated Purchase Order Reordering System
- **Plain English**: Automatically generate draft Purchase Orders (`PurchaseOrder` & `POLineItem` entities) when items drop to `LOW_STOCK`.
- **Benefit**: Automates re-ordering from suppliers to prevent stockout delays.
- **Trade-off**: Introduces Supplier relationships and PO state machine logic.
- **Interview Answer**: *"We connected the `LowStockEvent` pipeline to an automated PO generation engine that calculates optimal reorder quantities based on supplier lead times."*
- **Manual Test Steps**:
  1. Trigger low stock event for an item.
  2. Query `GET /api/v1/purchase-orders` to verify generated draft PO.

### Option 2: GitHub Actions CI Workflow Setup
- **Plain English**: Add automated GitHub Actions CI pipeline running PHPUnit tests, Doctrine schema validation, and linting on every git push.
- **Benefit**: Guarantees codebase health and prevents broken code from being merged.
- **Trade-off**: Requires configuring YAML workflow files in `.github/workflows/ci.yml`.
- **Interview Answer**: *"We configured a GitHub Actions CI matrix running PHPUnit test suites and Doctrine schema validation checks against SQLite databases on every pull request."*
- **Manual Test Steps**:
  1. Inspect `.github/workflows/ci.yml`.
  2. Trigger git push and view GitHub Actions green checkmark.

### Option 3: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.

### Option 4: Stock Expiration & Lot/Batch Number Tracking
- **Plain English**: Support batch numbers and expiration dates for perishable items (`BatchLot` entity with expiration alerts).
- **Benefit**: Essential for food, beverage, pharmaceutical, and chemical inventory management.
- **Trade-off**: Requires tracking FEFO (First Expired, First Out) picking logic.
- **Interview Answer**: *"We introduced a `BatchLot` entity tracking manufacturing dates and expiration windows, enforcing FEFO inventory allocation during sales fulfillment."*
- **Manual Test Steps**:
  1. Create product with 2 batch lots having different expiration dates.
  2. Deduct stock and verify FEFO picking assigns stock from the earliest expiring lot.
