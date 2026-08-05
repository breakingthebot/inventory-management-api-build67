# Iteration 2 Summary — Low-Stock Alerts & Email/Webhook Notifications

## Plain English Summary
In Iteration 2 of Build 67, we added an automated **Low-Stock Alerting & Webhook Notification Pipeline** to the Symfony Inventory Management API.

When a stock deduction or sales movement causes an item's quantity to drop below its minimum threshold (`minStockLevel`), `StockManager` fires a `LowStockEvent`. An event subscriber (`LowStockSubscriber`) receives the event and triggers `NotificationService`:
1. Formats a low-stock alert email payload for the warehouse management team.
2. Dispatches HTTP POST Webhooks to registered external subscribers, securing payloads with cryptographic HMAC-SHA256 signatures (`X-Inventory-Signature`).
3. Logs every notification dispatch in the `notification_logs` database table.

---

## Created and Modified Files

| File | Purpose | Connections |
| --- | --- | --- |
| `src/Event/LowStockEvent.php` | Event object carrying product status transition data | `src/Service/StockManager.php`, `src/EventSubscriber/LowStockSubscriber.php` |
| `src/Entity/WebhookSubscription.php` | Doctrine entity for webhook subscriber URLs and HMAC secrets | `src/Repository/WebhookSubscriptionRepository.php` |
| `src/Repository/WebhookSubscriptionRepository.php` | Doctrine repository for active webhook subscriptions | `src/Service/NotificationService.php` |
| `src/Entity/NotificationLog.php` | Doctrine entity for logging outbound alert delivery history | `src/Repository/NotificationLogRepository.php` |
| `src/Repository/NotificationLogRepository.php` | Doctrine repository for notification logs | `src/Service/NotificationService.php` |
| `src/Service/NotificationService.php` | Outbound delivery service for email alerts, HMAC signatures, and webhooks | `src/EventSubscriber/LowStockSubscriber.php` |
| `src/EventSubscriber/LowStockSubscriber.php` | EventSubscriber handling `LowStockEvent` dispatches | `src/Event/LowStockEvent.php`, `src/Service/NotificationService.php` |
| `src/Service/StockManager.php` | Updated to inject `EventDispatcherInterface` and dispatch `LowStockEvent` | `src/Event/LowStockEvent.php` |
| `src/Controller/WebhookController.php` | REST API controller for managing webhook subscriptions and reading logs | `POST/GET/DELETE /api/v1/webhooks/subscriptions`, `GET /api/v1/notifications/logs` |
| `tests/Service/NotificationServiceTest.php` | PHPUnit unit tests for HMAC signature calculation and event dispatches | `src/Service/NotificationService.php` |
| `tests/Service/StockManagerTest.php` | Updated to assert event dispatching on low stock transitions | `src/Service/StockManager.php` |
| `README.md` | Updated API reference and architecture notes | Repo Root |
| `CHANGELOG.md` | Updated Keep a Changelog entries for Iteration 2 | Repo Root |
| `BUILD_NOTES.md` | Appended plain English build log for Iteration 2 | `.gitignore` (local only) |
| `docs/summaries/iteration_02_summary.md` | Saved persistent summary for Iteration 2 | Documentation archive |

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
   *(Expected output: `OK (9 tests, 27 assertions)`)*

5. **Start Development Server**:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. **Execute HTTP API Webhook & Alert Verification in PowerShell**:
   ```powershell
   # 1. Register Webhook Subscriber
   $sub = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/webhooks/subscriptions" -Method Post -ContentType "application/json" -Body '{"url": "https://webhook.site/my-test-listener"}'
   Write-Host "Registered Webhook Subscriber:" ($sub | ConvertTo-Json -Compress)

   # 2. Trigger Low-Stock Alert (Deduct stock of product 1 below min_stock_level)
   $stk = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/products/1/stock" -Method Post -ContentType "application/json" -Body '{"type": "OUT", "quantity": 3, "reason": "Customer sale", "reference": "SO-2002"}'
   Write-Host "Stock Adjustment Output:" ($stk | ConvertTo-Json -Compress)

   # 3. Inspect Outbound Notification & Webhook Logs
   $logs = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/notifications/logs"
   Write-Host "Notification Audit Logs:" ($logs | ConvertTo-Json -Compress)
   ```

---

## Candidate Next Iterations

### Option 1: Multi-Warehouse Location Management
- **Plain English**: Track inventory stock per physical location (`Warehouse` entity with `WarehouseStock` join table).
- **Benefit**: Enables multi-branch retailers or logistics companies with multiple fulfillment hubs to manage stock per location while seeing global rollups.
- **Trade-off**: Increases database schema complexity and requires location parameters on stock movements.
- **Interview Answer**: *"We normalized inventory distribution by introducing a `Warehouse` entity and `WarehouseStock` join table, tracking per-location stock while maintaining global rollups."*
- **Manual Test Steps**:
  1. Create warehouses `WH-EAST` and `WH-WEST`.
  2. Transfer 5 units from `WH-EAST` to `WH-WEST` via stock transfer endpoint.

### Option 2: Bulk Import & Export Utility (CSV Format)
- **Plain English**: Upload CSV spreadsheets to bulk create/update products and download full CSV stock reports.
- **Benefit**: Saves warehouse staff hundreds of hours of manual entry during periodic stock-takes.
- **Trade-off**: Requires stream parsing and multi-row validation error aggregation.
- **Interview Answer**: *"We built a stream-based CSV importer with Symfony Validator row parsing, ensuring full batch accounting where bad rows are reported cleanly without corrupting valid records."*
- **Manual Test Steps**:
  1. Upload a CSV file containing 50 products.
  2. Verify JSON summary reporting successful imports and error rows.

### Option 3: LexikJWT Authentication & Role-Based Access Control (RBAC)
- **Plain English**: Secure write endpoints with JWT tokens and user roles (`ROLE_ADMIN`, `ROLE_WAREHOUSE`, `ROLE_AUDITOR`).
- **Benefit**: Restricts stock adjustments and deletion capabilities to authorized personnel.
- **Trade-off**: Requires passing Bearer tokens in headers across all integration tests.
- **Interview Answer**: *"We integrated LexikJWTAuthenticationBundle and Symfony Security Voters to enforce role-based authorization across stock write operations."*
- **Manual Test Steps**:
  1. Request JWT token via `/api/v1/login`.
  2. Perform POST with `Authorization: Bearer <token>` and verify 201 Created.

### Option 4: Automated Purchase Order Reordering (PO System)
- **Plain English**: Automatically generate draft Purchase Orders (`PurchaseOrder` & `POLineItem` entities) when items drop to `LOW_STOCK`.
- **Benefit**: Automates re-ordering from suppliers to prevent stockout delays.
- **Trade-off**: Introduces Supplier relationships and PO state machine logic.
- **Interview Answer**: *"We connected the `LowStockEvent` pipeline to an automated PO generation engine that calculates optimal reorder quantities based on supplier lead times."*
- **Manual Test Steps**:
  1. Trigger low stock event for an item.
  2. Query `GET /api/v1/purchase-orders` to verify generated draft PO.
