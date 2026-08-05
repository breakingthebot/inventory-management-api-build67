<?php

// src/Service/WebhookRetryEngine.php
// Asynchronous retry engine enforcing exponential backoff math and circuit breaker protection for webhook dispatches.
// Connects to: src/Entity/WebhookRetryQueue.php, src/Entity/WebhookSubscription.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\WebhookRetryQueue;
use App\Entity\WebhookSubscription;
use App\Repository\WebhookRetryQueueRepository;
use App\Repository\WebhookSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class WebhookRetryEngine
{
    public function __construct(
        private readonly WebhookRetryQueueRepository $retryQueueRepository,
        private readonly WebhookSubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Enqueues or updates a failed webhook retry entry with exponential backoff.
     */
    public function scheduleRetry(
        WebhookSubscription $subscription,
        string $eventName,
        array $payload,
        string $errorMessage,
        ?WebhookRetryQueue $existingRetry = null
    ): WebhookRetryQueue {
        $retry = $existingRetry ?? new WebhookRetryQueue();
        $retry->setSubscription($subscription);
        $retry->setEventName($eventName);
        $retry->setPayload($payload);
        $retry->setLastErrorMessage(substr($errorMessage, 0, 250));

        $attempts = $retry->getAttemptCount() + 1;
        $retry->setAttemptCount($attempts);

        if ($attempts >= $retry->getMaxAttempts()) {
            // Trip Circuit Breaker: disable subscription after max consecutive failures
            $retry->setStatus(WebhookRetryQueue::STATUS_FAILED);
            $subscription->setActive(false);
            $this->subscriptionRepository->save($subscription, false);
        } else {
            // Exponential Backoff Formula: 10 * 2^(attempts-1) seconds (10s, 20s, 40s, 80s...)
            $delaySeconds = (int)(10 * pow(2, $attempts - 1));
            $retry->setNextAttemptAt((new \DateTimeImmutable())->modify(sprintf('+%d seconds', $delaySeconds)));
            $retry->setStatus(WebhookRetryQueue::STATUS_PENDING);
        }

        $this->retryQueueRepository->save($retry, true);

        return $retry;
    }

    /**
     * Executes pending due retries in batch.
     * @return array Summary of processed retry attempts
     */
    public function processDueRetries(): array
    {
        $dueRetries = $this->retryQueueRepository->findDueRetries(50);
        $results = [
            'total_processed' => count($dueRetries),
            'delivered' => 0,
            'retried' => 0,
            'circuit_broken' => 0,
        ];

        foreach ($dueRetries as $retry) {
            $sub = $retry->getSubscription();
            if (!$sub || !$sub->isActive()) {
                $retry->setStatus(WebhookRetryQueue::STATUS_FAILED);
                $retry->setLastErrorMessage('Subscription deactivated by circuit breaker.');
                $results['circuit_broken']++;
                continue;
            }

            $jsonBody = json_encode($retry->getPayload());
            $signature = hash_hmac('sha256', $jsonBody, $sub->getSecret());

            $ch = curl_init($sub->getTargetUrl());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Inventory-Event: ' . $retry->getEventName(),
                'X-Inventory-Signature: ' . $signature,
                'User-Agent: Symfony-Inventory-WebhookClient/1.0',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $responseBody = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $retry->setStatus(WebhookRetryQueue::STATUS_DELIVERED);
                $results['delivered']++;
            } else {
                $errMsg = $curlErr ? $curlErr : sprintf('HTTP status code %d returned', $httpCode);
                $this->scheduleRetry($sub, $retry->getEventName(), $retry->getPayload(), $errMsg, $retry);
                $results['retried']++;
            }
        }

        $this->entityManager->flush();

        return $results;
    }
}
