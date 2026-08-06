<?php

// src/Service/PurchaseOrderGenerator.php
// Domain service calculating reorder formulas, generating draft POs, and processing receiving goods shipments.
// Connects to: src/Entity/Product.php, src/Entity/PurchaseOrder.php, src/Entity/PurchaseOrderItem.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\StockMovement;
use App\Entity\Supplier;
use App\Entity\Warehouse;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;

class PurchaseOrderGenerator
{
    public function __construct(
        private readonly PurchaseOrderRepository $poRepository,
        private readonly SupplierRepository $supplierRepository,
        private readonly StockManager $stockManager,
        private readonly WarehouseManager $warehouseManager,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Calculates recommended reorder quantity and generates/updates a DRAFT Purchase Order.
     */
    public function generateReorderPO(Product $product): PurchaseOrder
    {
        $minStock = $product->getMinStockLevel();
        $currentStock = $product->getStockQuantity();

        // Formula: Target stock is 2x minStockLevel, reorder quantity covers the deficit (min 10 units)
        $reorderQty = max(10, ($minStock * 2) - $currentStock);

        $supplier = $this->getOrCreateDefaultSupplier();

        // Check for existing DRAFT PO for this supplier
        $po = $this->poRepository->findOneBy(['supplier' => $supplier, 'status' => PurchaseOrder::STATUS_DRAFT]);
        if (!$po) {
            $po = new PurchaseOrder();
            $po->setSupplier($supplier);
            $po->setPoNumber('PO-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)));
            $po->setStatus(PurchaseOrder::STATUS_DRAFT);
            $po->setExpectedDeliveryDate((new \DateTimeImmutable())->modify(sprintf('+%d days', $supplier->getLeadTimeDays())));
        }

        // Check if product line item already exists in PO
        $existingItem = null;
        foreach ($po->getItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantityOrdered($existingItem->getQuantityOrdered() + $reorderQty);
        } else {
            $item = new PurchaseOrderItem();
            $item->setProduct($product);
            $item->setQuantityOrdered($reorderQty);
            $item->setUnitCost((float)$product->getUnitPrice() * 0.7); // Estimated wholesale cost (70% of retail)
            $po->addItem($item);
        }

        $this->poRepository->save($po, true);

        return $po;
    }

    /**
     * Receives shipment for a Purchase Order and automatically adds stock to inventory.
     */
    public function receiveGoods(PurchaseOrder $po, ?Warehouse $targetWarehouse = null): void
    {
        if ($po->getStatus() === PurchaseOrder::STATUS_RECEIVED) {
            throw new \InvalidArgumentException('Purchase Order has already been marked as RECEIVED.');
        }

        foreach ($po->getItems() as $item) {
            $qty = $item->getQuantityOrdered();
            $item->setQuantityReceived($qty);

            $reason = sprintf('Restock received from %s (%s)', $po->getPoNumber(), $po->getSupplier()?->getName() ?? 'Supplier');
            $ref = $po->getPoNumber();

            if ($targetWarehouse !== null) {
                $this->warehouseManager->recordWarehouseMovement($targetWarehouse, $item->getProduct(), StockMovement::TYPE_IN, $qty, $reason, $ref);
            } else {
                $this->stockManager->recordMovement($item->getProduct(), StockMovement::TYPE_IN, $qty, $reason, $ref);
            }
        }

        $po->setStatus(PurchaseOrder::STATUS_RECEIVED);
        $po->setReceivedAt(new \DateTimeImmutable());
        $this->poRepository->save($po, true);
    }

    private function getOrCreateDefaultSupplier(): Supplier
    {
        $supplier = $this->supplierRepository->findOneBy(['code' => 'SUP-ACME']);
        if (!$supplier) {
            $supplier = new Supplier();
            $supplier->setCode('SUP-ACME');
            $supplier->setName('Acme Global Wholesale');
            $supplier->setContactEmail('orders@acmewholesale.internal');
            $supplier->setLeadTimeDays(5);
            $this->supplierRepository->save($supplier, true);
        }
        return $supplier;
    }
}
