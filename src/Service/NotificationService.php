<?php

// src/Service/NotificationService.php
// Outbound notification delivery service for formatting low-stock alerts, HMAC signing, and webhook dispatches.
// Connects to: src/Event/LowStockEvent.php, src/Repository/WebhookSubscriptionRepository.php, src/Repository/NotificationLogRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\NotificationLog;
use App\Event\LowStockEvent;
use App\Repository\NotificationLogRepository;
use App\Repository\WebhookSubscriptionRepository;

class NotificationService
{
    public function __construct(
        private readonly WebhookSubscriptionRepository $webhookRepository,
        private readonly NotificationLogRepository $notificationLogRepository
    ) {
    }

    public function handleLowStockAlert(LowStockEvent $event): void
    {
        $product = $event->getProduct();
        $payload = [
            'event' => LowStockEvent::NAME,
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'stock_quantity' => $product->getStockQuantity(),
            'min_stock_level' => $product->getMinStockLevel(),
            'previous_status' => $event->getPreviousStatus(),
            'current_status' => $event->getCurrentStatus(),
            'timestamp' => $event->getTriggeredAt()->format(\DateTimeInterface::ATOM),
        ];

        // 1. Simulate Email Notification to Warehouse Manager
        $this->sendEmailAlert($payload);

        // 2. Dispatch Webhooks to all active subscribers
        $subscribers = $this->webhookRepository->findActiveSubscribers(LowStockEvent::NAME);
        foreach ($subscribers as $subscriber) {
            $this->dispatchWebhook($subscriber->getUrl(), $subscriber->getSecret(), $payload);
        }
    }

    public function sendEmailAlert(array $payload): void
    {
        $log = new NotificationLog();
        $log->setChannel(NotificationLog::TYPE_EMAIL);
        $log->setRecipient('warehouse-alerts@company.internal');
        $log->setEvent($payload['event']);
        $log->setPayload($payload);
        $log->setStatus(NotificationLog::STATUS_SENT);
        $log->setResponseCode(200);

        $this->notificationLogRepository->save($log, true);
    }

    public function dispatchWebhook(string $url, string $secret, array $payload): string
    {
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        $log = new NotificationLog();
        $log->setChannel(NotificationLog::TYPE_WEBHOOK);
        $log->setRecipient($url);
        $log->setEvent($payload['event']);
        $log->setPayload(array_merge($payload, ['_hmac_signature' => $signature]));

        // Simulated HTTP POST dispatch
        $log->setStatus(NotificationLog::STATUS_SENT);
        $log->setResponseCode(202);

        $this->notificationLogRepository->save($log, true);

        return $signature;
    }
}
