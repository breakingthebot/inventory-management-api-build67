<?php

// tests/Service/PurchaseOrderGeneratorTest.php
// Unit tests for PurchaseOrderGenerator service verifying PO reorder formulas, draft POs, and receiving shipments.
// Connects to: src/Service/PurchaseOrderGenerator.php, src/Entity/PurchaseOrder.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SupplierRepository;
use App\Service\PurchaseOrderGenerator;
use App\Service\StockManager;
use App\Service\WarehouseManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PurchaseOrderGeneratorTest extends TestCase
{
    private PurchaseOrderRepository $poRepository;
    private SupplierRepository $supplierRepository;
    private StockManager $stockManager;
    private WarehouseManager $warehouseManager;
    private EntityManagerInterface $entityManager;
    private PurchaseOrderGenerator $generator;

    protected function setUp(): void
    {
        $this->poRepository = $this->createMock(PurchaseOrderRepository::class);
        $this->supplierRepository = $this->createMock(SupplierRepository::class);
        $this->stockManager = $this->createMock(StockManager::class);
        $this->warehouseManager = $this->createMock(WarehouseManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->generator = new PurchaseOrderGenerator(
            $this->poRepository,
            $this->supplierRepository,
            $this->stockManager,
            $this->warehouseManager,
            $this->entityManager
        );
    }

    public function testGenerateReorderPOCreatesDraftWithCalculatedQuantity(): void
    {
        $product = new Product();
        $product->setSku('REORDER-001');
        $product->setUnitPrice(100.0);
        $product->setStockQuantity(2);
        $product->setMinStockLevel(10); // Target stock = 20, Deficit = 18

        $supplier = new Supplier();
        $supplier->setCode('SUP-ACME');
        $supplier->setLeadTimeDays(5);

        $this->supplierRepository->method('findOneBy')->willReturn($supplier);
        $this->poRepository->method('findOneBy')->willReturn(null);

        $this->poRepository->expects($this->once())->method('save');

        $po = $this->generator->generateReorderPO($product);

        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $po->getStatus());
        $this->assertCount(1, $po->getItems());

        $item = $po->getItems()->first();
        $this->assertEquals(18, $item->getQuantityOrdered());
        $this->assertEquals(70.0, (float)$item->getUnitCost());
    }

    public function testReceiveGoodsUpdatesPOStatusAndAddsStock(): void
    {
        $product = new Product();
        $product->setSku('REC-001');
        $product->setStockQuantity(0);

        $po = new PurchaseOrder();
        $po->setPoNumber('PO-TEST-123');
        $po->setStatus(PurchaseOrder::STATUS_SUBMITTED);

        $item = new \App\Entity\PurchaseOrderItem();
        $item->setProduct($product);
        $item->setQuantityOrdered(25);
        $po->addItem($item);

        $this->stockManager->expects($this->once())
            ->method('recordMovement')
            ->with($product, \App\Entity\StockMovement::TYPE_IN, 25);

        $this->poRepository->expects($this->once())->method('save')->with($po);

        $this->generator->receiveGoods($po, null);

        $this->assertEquals(PurchaseOrder::STATUS_RECEIVED, $po->getStatus());
        $this->assertEquals(25, $item->getQuantityReceived());
    }
}
