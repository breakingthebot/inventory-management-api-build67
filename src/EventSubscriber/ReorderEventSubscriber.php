<?php

// src/EventSubscriber/ReorderEventSubscriber.php
// Event subscriber listening to LowStockEvent and automatically triggering Purchase Order draft generation.
// Connects to: src/Event/LowStockEvent.php, src/Service/PurchaseOrderGenerator.php
// Created: 2026-08-05

namespace App\EventSubscriber;

use App\Event\LowStockEvent;
use App\Service\PurchaseOrderGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReorderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PurchaseOrderGenerator $poGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LowStockEvent::NAME => 'onLowStockTriggerReorder',
        ];
    }

    public function onLowStockTriggerReorder(LowStockEvent $event): void
    {
        $this->poGenerator->generateReorderPO($event->getProduct());
    }
}
