<?php

// src/EventSubscriber/LowStockSubscriber.php
// Event subscriber listening to LowStockEvent and invoking NotificationService dispatches.
// Connects to: src/Event/LowStockEvent.php, src/Service/NotificationService.php
// Created: 2026-08-05

namespace App\EventSubscriber;

use App\Event\LowStockEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LowStockSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LowStockEvent::NAME => 'onLowStockAlert',
        ];
    }

    public function onLowStockAlert(LowStockEvent $event): void
    {
        $this->notificationService->handleLowStockAlert($event);
    }
}
