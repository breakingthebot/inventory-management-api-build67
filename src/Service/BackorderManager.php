<?php

// src/Service/BackorderManager.php
// Domain service managing customer backorder queues and FIFO stock fulfillment allocations.
// Connects to: src/Entity/Backorder.php, src/Entity/Product.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Backorder;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Repository\BackorderRepository;

class BackorderManager
{
    public function __construct(
        private readonly BackorderRepository $backorderRepository,
        private readonly StockManager $stockManager
    ) {
    }

    /**
     * Enqueues a customer backorder request for an item.
     */
    public function createBackorder(Product $product, string $customerEmail, int $quantity = 1): Backorder
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Backorder quantity must be greater than zero.');
        }

        $backorder = new Backorder();
        $backorder->setProduct($product);
        $backorder->setCustomerEmail($customerEmail);
        $backorder->setQuantity($quantity);
        $backorder->setBackorderNumber('BO-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
        $backorder->setStatus(Backorder::STATUS_PENDING);

        $this->backorderRepository->save($backorder, true);

        return $backorder;
    }

    /**
     * Allocates available physical inventory stock to pending backorders in FIFO queue order.
     * @return Backorder[] Array of fulfilled backorders
     */
    public function allocateStockToBackorders(Product $product): array
    {
        $pendingList = $this->backorderRepository->findPendingBackordersForProduct($product);
        $fulfilledList = [];

        foreach ($pendingList as $backorder) {
            $availableStock = $product->getStockQuantity();
            $neededQuantity = $backorder->getQuantity();

            if ($availableStock >= $neededQuantity) {
                // Fulfill backorder
                $backorder->setStatus(Backorder::STATUS_FULFILLED);
                $this->backorderRepository->save($backorder, false);

                // Deduct physical inventory
                $this->stockManager->recordMovement(
                    $product,
                    StockMovement::TYPE_OUT,
                    $neededQuantity,
                    sprintf('FIFO Backorder fulfillment (%s)', $backorder->getBackorderNumber()),
                    $backorder->getBackorderNumber()
                );

                $fulfilledList[] = $backorder;
            }
        }

        $this->backorderRepository->save($backorder ?? new Backorder(), true);

        return $fulfilledList;
    }
}
