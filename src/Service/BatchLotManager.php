<?php

// src/Service/BatchLotManager.php
// Domain service executing FEFO (First Expired, First Out) stock allocation and batch lot management.
// Connects to: src/Entity/BatchLot.php, src/Entity/Product.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\BatchLot;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Repository\BatchLotRepository;
use Doctrine\ORM\EntityManagerInterface;

class BatchLotManager
{
    public function __construct(
        private readonly BatchLotRepository $batchLotRepository,
        private readonly StockManager $stockManager,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new BatchLot and adds stock quantity to Product.
     */
    public function createBatchLot(
        Product $product,
        string $batchNumber,
        int $quantity,
        \DateTimeImmutable $expirationDate,
        ?\DateTimeImmutable $mfgDate = null
    ): BatchLot {
        $lot = new BatchLot();
        $lot->setProduct($product);
        $lot->setBatchNumber($batchNumber);
        $lot->setQuantity($quantity);
        $lot->setExpirationDate($expirationDate);
        $lot->setManufacturingDate($mfgDate);

        $this->batchLotRepository->save($lot, false);

        // Record stock movement for new batch lot restock
        $this->stockManager->recordMovement(
            $product,
            StockMovement::TYPE_IN,
            $quantity,
            sprintf('Batch lot restock (%s)', $lot->getBatchNumber()),
            $lot->getBatchNumber()
        );

        $this->entityManager->flush();

        return $lot;
    }

    /**
     * Deducts stock using First Expired, First Out (FEFO) logic.
     * @return array Summary of lot deductions
     */
    public function allocateFefoStock(Product $product, int $quantityToDeduct): array
    {
        if ($quantityToDeduct <= 0) {
            throw new \InvalidArgumentException('Quantity to deduct must be greater than zero.');
        }

        $fefoLots = $this->batchLotRepository->findFefoLots($product);
        $remainingToDeduct = $quantityToDeduct;
        $allocations = [];

        foreach ($fefoLots as $lot) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $currentLotQty = $lot->getQuantity();
            $deductFromLot = min($currentLotQty, $remainingToDeduct);

            $lot->setQuantity($currentLotQty - $deductFromLot);
            $remainingToDeduct -= $deductFromLot;

            $allocations[] = [
                'batch_number' => $lot->getBatchNumber(),
                'quantity_deducted' => $deductFromLot,
                'expiration_date' => $lot->getExpirationDate()?->format('Y-m-d'),
                'remaining_lot_stock' => $lot->getQuantity(),
            ];
        }

        if ($remainingToDeduct > 0) {
            throw new \RuntimeException(sprintf(
                'Insufficient batch lot stock to fulfill FEFO deduction of %d units for SKU "%s". Short fall: %d units.',
                $quantityToDeduct,
                $product->getSku(),
                $remainingToDeduct
            ));
        }

        $this->stockManager->recordMovement(
            $product,
            StockMovement::TYPE_OUT,
            $quantityToDeduct,
            sprintf('FEFO order fulfillment across %d batch lots', count($allocations)),
            'FEFO-FULFILLMENT'
        );

        $this->entityManager->flush();

        return $allocations;
    }
}
