<?php

// tests/Service/NotificationServiceTest.php
// Unit tests verifying HMAC signature computation, email formatting, and notification logging.
// Connects to: src/Service/NotificationService.php, src/Event/LowStockEvent.php, src/Entity/NotificationLog.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\NotificationLog;
use App\Entity\Product;
use App\Entity\WebhookSubscription;
use App\Event\LowStockEvent;
use App\Repository\NotificationLogRepository;
use App\Repository\WebhookSubscriptionRepository;
use App\Service\NotificationService;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    private WebhookSubscriptionRepository $webhookRepository;
    private NotificationLogRepository $notificationLogRepository;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->webhookRepository = $this->createMock(WebhookSubscriptionRepository::class);
        $this->notificationLogRepository = $this->createMock(NotificationLogRepository::class);
        $this->notificationService = new NotificationService(
            $this->webhookRepository,
            $this->notificationLogRepository
        );
    }

    public function testHmacSignatureGeneration(): void
    {
        $url = 'https://webhook.site/test-receiver';
        $secret = 'super_secret_key_12345';
        $payload = ['event' => 'inventory.low_stock', 'sku' => 'TEST-SKU'];

        $this->notificationLogRepository->expects($this->once())->method('save');

        $signature = $this->notificationService->dispatchWebhook($url, $secret, $payload);

        $expectedJson = json_encode($payload);
        $expectedSignature = hash_hmac('sha256', $expectedJson, $secret);

        $this->assertEquals($expectedSignature, $signature);
    }

    public function testHandleLowStockAlertDispatchesEmailAndWebhooks(): void
    {
        $product = new Product();
        $product->setSku('LOW-001');
        $product->setName('Low Stock Keyboard');
        $product->setStockQuantity(2);
        $product->setMinStockLevel(5);

        $subscription = new WebhookSubscription();
        $subscription->setUrl('https://api.partner.com/webhooks');

        $this->webhookRepository->method('findActiveSubscribers')->willReturn([$subscription]);
        $this->notificationLogRepository->expects($this->exactly(2))->method('save');

        $event = new LowStockEvent($product, Product::STATUS_IN_STOCK, Product::STATUS_LOW_STOCK);
        $this->notificationService->handleLowStockAlert($event);
    }
}
