<?php

// tests/Service/WebhookRetryEngineTest.php
// Unit tests for WebhookRetryEngine verifying exponential backoff delay calculations and circuit breaker tripping logic.
// Connects to: src/Service/WebhookRetryEngine.php, src/Entity/WebhookRetryQueue.php, src/Entity/WebhookSubscription.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\WebhookRetryQueue;
use App\Entity\WebhookSubscription;
use App\Repository\WebhookRetryQueueRepository;
use App\Repository\WebhookSubscriptionRepository;
use App\Service\WebhookRetryEngine;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WebhookRetryEngineTest extends TestCase
{
    private WebhookRetryQueueRepository $retryQueueRepository;
    private WebhookSubscriptionRepository $subscriptionRepository;
    private EntityManagerInterface $entityManager;
    private WebhookRetryEngine $engine;

    protected function setUp(): void
    {
        $this->retryQueueRepository = $this->createMock(WebhookRetryQueueRepository::class);
        $this->subscriptionRepository = $this->createMock(WebhookSubscriptionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->engine = new WebhookRetryEngine(
            $this->retryQueueRepository,
            $this->subscriptionRepository,
            $this->entityManager
        );
    }

    public function testScheduleRetryCalculatesExponentialBackoff(): void
    {
        $sub = new WebhookSubscription();
        $sub->setUrl('https://webhook.internal/listener');

        $this->retryQueueRepository->expects($this->once())->method('save');

        $retry = $this->engine->scheduleRetry($sub, 'low_stock.alert', ['sku' => 'TEST-1'], '500 Server Error');

        $this->assertEquals(1, $retry->getAttemptCount());
        $this->assertEquals(WebhookRetryQueue::STATUS_PENDING, $retry->getStatus());
        $this->assertTrue($sub->isActive());

        // Attempt 1: 10 * 2^0 = 10s delay
        $diff = $retry->getNextAttemptAt()->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();
        $this->assertGreaterThanOrEqual(9, $diff);
        $this->assertLessThanOrEqual(11, $diff);
    }

    public function testCircuitBreakerTripsAfterMaxAttempts(): void
    {
        $sub = new WebhookSubscription();
        $sub->setUrl('https://broken-webhook.internal/listener');
        $sub->setActive(true);

        $retry = new WebhookRetryQueue();
        $retry->setAttemptCount(4); // 4 attempts already failed, next will be 5th

        $this->subscriptionRepository->expects($this->once())->method('save')->with($sub);
        $this->retryQueueRepository->expects($this->once())->method('save')->with($retry);

        $result = $this->engine->scheduleRetry($sub, 'low_stock.alert', ['sku' => 'TEST-1'], '503 Service Unavailable', $retry);

        $this->assertEquals(5, $result->getAttemptCount());
        $this->assertEquals(WebhookRetryQueue::STATUS_FAILED, $result->getStatus());
        $this->assertFalse($sub->isActive()); // Circuit breaker tripped!
    }
}
