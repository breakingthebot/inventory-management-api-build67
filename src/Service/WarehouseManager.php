<?php

// src/Service/WarehouseManager.php
// Domain service managing multi-warehouse stock allocations, inter-warehouse transfers, and global stock rollup sync.
// Connects to: src/Entity/Warehouse.php, src/Entity/WarehouseStock.php, src/Entity/Product.php, src/Entity/StockMovement.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\Warehouse;
use App\Entity\WarehouseStock;
use App\Event\LowStockEvent;
use App\Repository\WarehouseStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WarehouseManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseStockRepository $warehouseStockRepository,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    public function recordWarehouseMovement(
        Warehouse $warehouse,
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

        $stockItem = $this->warehouseStockRepository->findOrCreate($warehouse, $product);
        $currentWarehouseStock = $stockItem->getStockQuantity();
        $newWarehouseStock = $currentWarehouseStock;

        if ($type === StockMovement::TYPE_IN) {
            $newWarehouseStock = $currentWarehouseStock + $quantity;
        } elseif ($type === StockMovement::TYPE_OUT) {
            if ($quantity > $currentWarehouseStock) {
                throw new \InvalidArgumentException(sprintf(
                    'Insufficient stock in warehouse "%s". Cannot deduct %d units from current stock of %d.',
                    $warehouse->getCode(),
                    $quantity,
                    $currentWarehouseStock
                ));
            }
            $newWarehouseStock = $currentWarehouseStock - $quantity;
        } elseif ($type === StockMovement::TYPE_ADJUST) {
            $newWarehouseStock = max(0, $quantity);
        }

        $stockItem->setStockQuantity($newWarehouseStock);
        $this->entityManager->persist($stockItem);

        // Sync global product stock sum
        $previousGlobalStatus = $product->getStatus();
        $globalSum = $this->warehouseStockRepository->getGlobalStockSum($product);
        // Add delta change for current uncommitted flush
        $delta = $newWarehouseStock - $currentWarehouseStock;
        $newGlobalStock = max(0, $globalSum + $delta);

        $product->setStockQuantity($newGlobalStock);
        $currentGlobalStatus = $product->getStatus();
        $this->entityManager->persist($product);

        $movement = new StockMovement();
        $movement->setProduct($product);
        $movement->setWarehouse($warehouse);
        $movement->setType($type);
        $movement->setQuantity($quantity);
        $movement->setResultingStock($newWarehouseStock);
        $movement->setReason($reason);
        $movement->setReference($reference);

        $this->entityManager->persist($movement);
        $this->entityManager->flush();

        // Dispatch LowStockEvent if global status transitioned into LOW_STOCK or OUT_OF_STOCK
        if (
            $this->eventDispatcher !== null &&
            in_array($currentGlobalStatus, [Product::STATUS_LOW_STOCK, Product::STATUS_OUT_OF_STOCK], true) &&
            $previousGlobalStatus !== $currentGlobalStatus
        ) {
            $this->eventDispatcher->dispatch(
                new LowStockEvent($product, $previousGlobalStatus, $currentGlobalStatus),
                LowStockEvent::NAME
            );
        }

        return $movement;
    }

    /**
     * Executes an inter-warehouse stock transfer.
     *
     * @return array{sourceMovement: StockMovement, targetMovement: StockMovement}
     */
    public function transferStock(
        Warehouse $sourceWarehouse,
        Warehouse $targetWarehouse,
        Product $product,
        int $quantity,
        ?string $reference = null
    ): array {
        if ($sourceWarehouse->getId() === $targetWarehouse->getId()) {
            throw new \InvalidArgumentException('Source and target warehouse must be different.');
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Transfer quantity must be greater than 0.');
        }

        $sourceStock = $this->warehouseStockRepository->findOrCreate($sourceWarehouse, $product);
        if ($sourceStock->getStockQuantity() < $quantity) {
            throw new \InvalidArgumentException(sprintf(
                'Insufficient stock in source warehouse "%s". Available: %d, Requested transfer: %d.',
                $sourceWarehouse->getCode(),
                $sourceStock->getStockQuantity(),
                $quantity
            ));
        }

        $targetStock = $this->warehouseStockRepository->findOrCreate($targetWarehouse, $product);

        // Deduct from source & add to target
        $sourceStock->setStockQuantity($sourceStock->getStockQuantity() - $quantity);
        $targetStock->setStockQuantity($targetStock->getStockQuantity() + $quantity);

        $this->entityManager->persist($sourceStock);
        $this->entityManager->persist($targetStock);

        $sourceMovement = new StockMovement();
        $sourceMovement->setProduct($product);
        $sourceMovement->setWarehouse($sourceWarehouse);
        $sourceMovement->setType(StockMovement::TYPE_TRANSFER);
        $sourceMovement->setQuantity(-$quantity);
        $sourceMovement->setResultingStock($sourceStock->getStockQuantity());
        $sourceMovement->setReason(sprintf('Transfer OUT to warehouse %s', $targetWarehouse->getCode()));
        $sourceMovement->setReference($reference);

        $targetMovement = new StockMovement();
        $targetMovement->setProduct($product);
        $targetMovement->setWarehouse($targetWarehouse);
        $targetMovement->setType(StockMovement::TYPE_TRANSFER);
        $targetMovement->setQuantity($quantity);
        $targetMovement->setResultingStock($targetStock->getStockQuantity());
        $targetMovement->setReason(sprintf('Transfer IN from warehouse %s', $sourceWarehouse->getCode()));
        $targetMovement->setReference($reference);

        $this->entityManager->persist($sourceMovement);
        $this->entityManager->persist($targetMovement);
        $this->entityManager->flush();

        return [
            'sourceMovement' => $sourceMovement,
            'targetMovement' => $targetMovement,
        ];
    }
}
