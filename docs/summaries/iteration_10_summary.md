# Iteration 10 Summary — API Rate Limiting & Sliding Window Throttle

## Plain English Summary
In Iteration 10 of Build 67, we protected the API against denial-of-service and brute-force attacks by implementing a **Sliding Window API Rate Limiter** (`RateLimiter` service & `RateLimitSubscriber`).

The rate limiter tracks client request quotas (default 60 requests per minute) using Bearer Tokens or client IP addresses. When a client exceeds their allocated quota on `/api/v1/*` routes, the system immediately returns an HTTP `429 Too Many Requests` JSON error response. Additionally, standard rate limiting headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `Retry-After`) are attached to every API response.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Service/RateLimiter.php` | Sliding window rate limiting service tracking request quotas per client key | `src/EventSubscriber/RateLimitSubscriber.php` |
| `src/EventSubscriber/RateLimitSubscriber.php` | Symfony KernelEvent subscriber intercepting requests, returning 429 status codes, and injecting rate limit headers | `src/Service/RateLimiter.php` |
| `tests/Service/RateLimiterTest.php` | PHPUnit unit tests verifying sliding window quota calculations and 429 throttling | `src/Service/RateLimiter.php` |
| `README.md` | Updated API reference, rate limiting headers, and 429 status code documentation | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 10 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 10 | `.gitignore` (local only) |
| `docs/summaries/iteration_10_summary.md` | Saved persistent summary for Iteration 10 | Documentation archive |

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
   *(Expected output: `OK (22 tests, 93 assertions)`)*

4. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

5. **Execute HTTP Verification & Rate Limit Throttling in PowerShell**:
   ```powershell
   # 1. Send Single Request & Inspect Headers
   $res = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/v1/health" -Method Get
   Write-Host "X-RateLimit-Limit:" $res.Headers['X-RateLimit-Limit']
   Write-Host "X-RateLimit-Remaining:" $res.Headers['X-RateLimit-Remaining']
   Write-Host "X-RateLimit-Reset:" $res.Headers['X-RateLimit-Reset']

   # 2. Rapidly Send Requests to Trigger HTTP 429 Throttling
   Write-Host "Sending rapid requests to test rate limit threshold..."
   for ($i = 1; $i -le 65; $i++) {
       try {
           $r = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/health" -Method Get
       } catch {
           Write-Host "Rate Limit Triggered at Request #$i (Expected 429):" $_.Exception.Message
           break
       }
   }
   ```

---

## Candidate Next Iterations

Please choose one of the following candidate iterations to continue:

### Option 1: Full Multi-Currency Pricing & Tax Rate Matrix
- **Plain English**: Add support for multiple currencies (`USD`, `EUR`, `GBP`, `CAD`) with dynamic exchange rate conversions and tax rate matrices.
- **Benefit**: Essential for international e-commerce and cross-border logistics.
- **Trade-off**: Requires precision currency conversion math and exchange rate updates.
- **Interview Answer**: *"We built a multi-currency pricing engine integrating external exchange rate APIs to convert product prices dynamically based on regional customer locales."*

### Option 2: Automated Inventory Audit Sampling & Stock Count Reconciliation
- **Plain English**: Generate periodic inventory audit cycles (`AuditCycle` & `AuditDiscrepancy` entities) to compare physical stock counts against system records.
- **Benefit**: Helps identify stock leakage, damage, or theft quickly.
- **Trade-off**: Adds audit workflow state machinery.
- **Interview Answer**: *"We created an inventory audit engine that generates random product sampling lists for physical count reconciliation and logs inventory variance adjustments."*

### Option 3: Webhook Failure Retry Queue & Circuit Breaker
- **Plain English**: Add an exponential backoff retry queue (`WebhookDeliveryRetry` entity) for failed HTTP webhook deliveries with circuit breaker thresholds.
- **Benefit**: Ensures reliable event delivery even when partner servers experience temporary downtime.
- **Trade-off**: Requires scheduled background retry processing.
- **Interview Answer**: *"We implemented an asynchronous retry queue with exponential backoff and circuit breaker logic for failed webhook dispatches."*

### Option 4: Stock Reservation Engine for E-Commerce Orders
- **Plain English**: Add temporary stock reservations (`StockReservation` entity with TTL timer) to hold inventory during checkout without immediately deducting stock.
- **Benefit**: Prevents overselling during high-traffic flash sales or shopping cart checkouts.
- **Trade-off**: Adds TTL reservation expiration background cleanup.
- **Interview Answer**: *"We implemented a Redis/Doctrine stock reservation system that holds inventory for 15 minutes during cart checkout before confirming or releasing held stock."*

---

Please let me know which candidate iteration you would like to proceed with!
