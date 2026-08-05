<?php

// src/Service/StockManager.php
// Business logic service managing atomic stock movements, status recalculation, audit logging, and LowStockEvent dispatch.
// Connects to: src/Entity/Product.php, src/Entity/StockMovement.php, src/Event/LowStockEvent.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Event\LowStockEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StockManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    /**
     * Executes a stock movement for a product and logs the transaction.
     *
     * @throws \InvalidArgumentException if parameters are invalid or stock becomes negative
     */
    public function recordMovement(
        Product $product,
        string $type,
        int $quantity,
        ?string $reason = null,
        ?string $reference = null
    ): StockMovement {
        $type = strtoupper(trim($type));
        if (!in_array($type, [StockMovement::TYPE_IN, StockMovement::TYPE_OUT, StockMovement::TYPE_ADJUST], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid movement type "%s". Allowed: IN, OUT, ADJUST.', $type));
        }

        if ($quantity <= 0 && $type !== StockMovement::TYPE_ADJUST) {
            throw new \InvalidArgumentException('Quantity for IN or OUT movements must be greater than 0.');
        }

        $previousStatus = $product->getStatus();
        $currentStock = $product->getStockQuantity();
        $newStock = $currentStock;

        if ($type === StockMovement::TYPE_IN) {
            $newStock = $currentStock + $quantity;
        } elseif ($type === StockMovement::TYPE_OUT) {
            if ($quantity > $currentStock) {
                throw new \InvalidArgumentException(sprintf(
                    'Insufficient stock. Cannot deduct %d units from current stock of %d.',
                    $quantity,
                    $currentStock
                ));
            }
            $newStock = $currentStock - $quantity;
        } elseif ($type === StockMovement::TYPE_ADJUST) {
            $newStock = max(0, $quantity);
        }

        $product->setStockQuantity($newStock);
        $currentStatus = $product->getStatus();

        $movement = new StockMovement();
        $movement->setProduct($product);
        $movement->setType($type);
        $movement->setQuantity($quantity);
        $movement->setResultingStock($newStock);
        $movement->setReason($reason);
        $movement->setReference($reference);

        $this->entityManager->persist($product);
        $this->entityManager->persist($movement);
        $this->entityManager->flush();

        // Dispatch LowStockEvent if status transitioned into LOW_STOCK or OUT_OF_STOCK
        if (
            $this->eventDispatcher !== null &&
            in_array($currentStatus, [Product::STATUS_LOW_STOCK, Product::STATUS_OUT_OF_STOCK], true) &&
            $previousStatus !== $currentStatus
        ) {
            $this->eventDispatcher->dispatch(
                new LowStockEvent($product, $previousStatus, $currentStatus),
                LowStockEvent::NAME
            );
        }

        return $movement;
    }
}
