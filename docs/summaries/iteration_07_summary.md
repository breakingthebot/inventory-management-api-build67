# Iteration 7 Summary — GitHub Actions CI Workflow Setup

## Plain English Summary
In Iteration 7 of Build 67, we added an automated **GitHub Actions Continuous Integration (CI)** pipeline (`.github/workflows/ci.yml`).

Every push or pull request to the `main` branch triggers an automated CI job on an Ubuntu runner running PHP 8.3. The pipeline automatically validates dependency manifests (`composer validate --strict`), checks Doctrine ORM entity mapping syntax (`doctrine:schema:validate`), and executes the complete 18-test PHPUnit suite to guarantee codebase stability.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `.github/workflows/ci.yml` | GitHub Actions CI workflow pipeline definition | GitHub Actions Runner |
| `README.md` | Updated with CI status badge, CI workflow documentation, and testing commands | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 7 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 7 | `.gitignore` (local only) |
| `docs/summaries/iteration_07_summary.md` | Saved persistent summary for Iteration 7 | Documentation archive |

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

3. **Run Composer Validation**:
   ```bash
   composer validate --strict
   ```
   *(Expected output: `./composer.json is valid`)*

4. **Run Doctrine ORM Schema Validation**:
   ```bash
   php bin/console doctrine:schema:validate --skip-sync
   ```
   *(Expected output: `[OK] The mapping files are correct.`)*

5. **Run PHPUnit Test Suite**:
   ```bash
   php vendor/phpunit/phpunit/phpunit
   ```
   *(Expected output: `OK (18 tests, 67 assertions)`)*

6. **Verify GitHub Actions Workflow Execution**:
   - Push a commit to `https://github.com/breakingthebot/inventory-management-api-build67.git`.
   - Open the **Actions** tab on GitHub to observe the green checkmark on the `Inventory Management API CI` workflow run.

---

## Candidate Next Iterations

### Option 1: Stock Expiration & Lot/Batch Number Tracking
- **Plain English**: Support batch numbers and expiration dates for perishable items (`BatchLot` entity with expiration alerts).
- **Benefit**: Essential for food, beverage, pharmaceutical, and chemical inventory management.
- **Trade-off**: Requires tracking FEFO (First Expired, First Out) picking logic.
- **Interview Answer**: *"We introduced a `BatchLot` entity tracking manufacturing dates and expiration windows, enforcing FEFO inventory allocation during sales fulfillment."*
- **Manual Test Steps**:
  1. Create product with 2 batch lots having different expiration dates.
  2. Deduct stock and verify FEFO picking assigns stock from the earliest expiring lot.

### Option 2: Interactive Admin Dashboard & Analytics UI
- **Plain English**: Add a responsive web dashboard for warehouse managers displaying stock levels, low-stock alert badges, PO statuses, and movement charts.
- **Benefit**: Provides visual oversight without relying exclusively on REST API clients.
- **Trade-off**: Adds frontend HTML/Twig template rendering assets.
- **Interview Answer**: *"We implemented a Symfony Twig dashboard with real-time stock metrics and chart visualizations for warehouse managers."*
- **Manual Test Steps**:
  1. Open `http://127.0.0.1:8000/admin/dashboard` in browser.
  2. Observe real-time stock status widgets and movement log feeds.

### Option 3: API Rate Limiting & Sliding Window Throttle
- **Plain English**: Add sliding window rate limiting (e.g., 60 requests per minute per IP/Token) to protect the API against denial-of-service or brute force attacks.
- **Benefit**: Protects server resources and guarantees API availability under heavy load.
- **Trade-off**: Adds rate limit headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`) and state tracking.
- **Interview Answer**: *"We integrated Symfony RateLimiter with sliding window policies to enforce per-ip and per-user quota controls."*
- **Manual Test Steps**:
  1. Send 61 rapid HTTP requests in a loop.
  2. Verify 61st request receives 429 Too Many Requests response.

### Option 4: Full Multi-Currency Pricing & Tax Rate Matrix
- **Plain English**: Add support for multiple currencies (`USD`, `EUR`, `GBP`, `CAD`) with dynamic exchange rate conversions and tax rate matrices.
- **Benefit**: Essential for international e-commerce and cross-border logistics.
- **Trade-off**: Requires precision currency conversion math and exchange rate updates.
- **Interview Answer**: *"We built a multi-currency pricing engine integrating external exchange rate APIs to convert product prices dynamically based on regional customer locales."*
- **Manual Test Steps**:
  1. Query `GET /api/v1/products?currency=EUR`.
  2. Observe converted prices based on live/mock exchange rates.
