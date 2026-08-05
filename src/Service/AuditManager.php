<?php

// src/Service/AuditManager.php
// Domain service managing audit sampling generation and inventory variance count reconciliations.
// Connects to: src/Entity/AuditCycle.php, src/Entity/AuditDiscrepancy.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\AuditCycle;
use App\Entity\AuditDiscrepancy;
use App\Entity\StockMovement;
use App\Entity\Warehouse;
use App\Repository\AuditCycleRepository;
use App\Repository\ProductRepository;
use App\Repository\WarehouseStockRepository;
use Doctrine\ORM\EntityManagerInterface;

class AuditManager
{
    public function __construct(
        private readonly AuditCycleRepository $auditCycleRepository,
        private readonly ProductRepository $productRepository,
        private readonly WarehouseStockRepository $warehouseStockRepository,
        private readonly StockManager $stockManager,
        private readonly WarehouseManager $warehouseManager,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Generates a new inventory audit cycle with random product sampling.
     */
    public function createAuditCycle(?Warehouse $warehouse = null, int $sampleSize = 5, ?string $notes = null): AuditCycle
    {
        $allProducts = $this->productRepository->findAll();
        if (empty($allProducts)) {
            throw new \RuntimeException('Cannot create audit cycle: No products exist in catalog.');
        }

        shuffle($allProducts);
        $sampledProducts = array_slice($allProducts, 0, max(1, min(count($allProducts), $sampleSize)));

        $cycle = new AuditCycle();
        $cycle->setWarehouse($warehouse);
        $cycle->setAuditCode('AUD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)));
        $cycle->setStatus(AuditCycle::STATUS_IN_PROGRESS);
        $cycle->setNotes($notes);

        foreach ($sampledProducts as $product) {
            $discrepancy = new AuditDiscrepancy();
            $discrepancy->setProduct($product);

            if ($warehouse !== null) {
                $ws = $this->warehouseStockRepository->findOneBy(['warehouse' => $warehouse, 'product' => $product]);
                $sysQty = $ws ? $ws->getQuantity() : 0;
            } else {
                $sysQty = $product->getStockQuantity();
            }

            $discrepancy->setSystemQuantity($sysQty);
            $cycle->addDiscrepancy($discrepancy);
        }

        $this->auditCycleRepository->save($cycle, true);

        return $cycle;
    }

    /**
     * Reconciles physical count entries and posts stock adjustments for any variance.
     */
    public function reconcileAuditCycle(AuditCycle $auditCycle, array $countedItems): AuditCycle
    {
        if ($auditCycle->getStatus() === AuditCycle::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Audit cycle has already been completed and reconciled.');
        }

        $countMap = [];
        foreach ($countedItems as $item) {
            if (isset($item['product_id'], $item['counted_quantity'])) {
                $countMap[(int)$item['product_id']] = (int)$item['counted_quantity'];
            }
        }

        $warehouse = $auditCycle->getWarehouse();

        foreach ($auditCycle->getDiscrepancies() as $discrepancy) {
            $productId = $discrepancy->getProduct()->getId();
            if (isset($countMap[$productId])) {
                $countedQty = $countMap[$productId];
                $discrepancy->setCountedQuantity($countedQty);
                $discrepancy->setReconciled(true);

                $variance = $discrepancy->getVarianceQuantity();
                if ($variance !== null && $variance !== 0) {
                    $reason = sprintf('Physical audit variance adjustment (%s)', $auditCycle->getAuditCode());
                    $ref = $auditCycle->getAuditCode();

                    if ($warehouse !== null) {
                        $this->warehouseManager->recordWarehouseMovement(
                            $warehouse,
                            $discrepancy->getProduct(),
                            StockMovement::TYPE_ADJUST,
                            $countedQty,
                            $reason,
                            $ref
                        );
                    } else {
                        $this->stockManager->recordMovement(
                            $discrepancy->getProduct(),
                            StockMovement::TYPE_ADJUST,
                            $countedQty,
                            $reason,
                            $ref
                        );
                    }
                }
            }
        }

        $auditCycle->setStatus(AuditCycle::STATUS_COMPLETED);
        $this->auditCycleRepository->save($auditCycle, true);

        return $auditCycle;
    }
}
