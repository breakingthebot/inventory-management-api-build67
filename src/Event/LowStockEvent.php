<?php

// src/Event/LowStockEvent.php
// Event object dispatched when a product stock level drops into LOW_STOCK or OUT_OF_STOCK.
// Connects to: src/Entity/Product.php, src/EventSubscriber/LowStockSubscriber.php
// Created: 2026-08-05

namespace App\Event;

use App\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

class LowStockEvent extends Event
{
    public const NAME = 'inventory.low_stock';

    private \DateTimeImmutable $triggeredAt;

    public function __construct(
        private readonly Product $product,
        private readonly string $previousStatus,
        private readonly string $currentStatus
    ) {
        $this->triggeredAt = new \DateTimeImmutable();
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getPreviousStatus(): string
    {
        return $this->previousStatus;
    }

    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    public function getTriggeredAt(): \DateTimeImmutable
    {
        return $this->triggeredAt;
    }
}
