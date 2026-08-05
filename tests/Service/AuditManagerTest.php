<?php

// tests/Service/AuditManagerTest.php
// Unit tests for AuditManager service verifying audit cycle creation, sampling, and count reconciliation.
// Connects to: src/Service/AuditManager.php, src/Entity/AuditCycle.php, src/Entity/AuditDiscrepancy.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\AuditCycle;
use App\Entity\AuditDiscrepancy;
use App\Entity\Product;
use App\Repository\AuditCycleRepository;
use App\Repository\ProductRepository;
use App\Repository\WarehouseStockRepository;
use App\Service\AuditManager;
use App\Service\StockManager;
use App\Service\WarehouseManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AuditManagerTest extends TestCase
{
    private AuditCycleRepository $auditCycleRepository;
    private ProductRepository $productRepository;
    private WarehouseStockRepository $warehouseStockRepository;
    private StockManager $stockManager;
    private WarehouseManager $warehouseManager;
    private EntityManagerInterface $entityManager;
    private AuditManager $manager;

    protected function setUp(): void
    {
        $this->auditCycleRepository = $this->createMock(AuditCycleRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->warehouseStockRepository = $this->createMock(WarehouseStockRepository::class);
        $this->stockManager = $this->createMock(StockManager::class);
        $this->warehouseManager = $this->createMock(WarehouseManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->manager = new AuditManager(
            $this->auditCycleRepository,
            $this->productRepository,
            $this->warehouseStockRepository,
            $this->stockManager,
            $this->warehouseManager,
            $this->entityManager
        );
    }

    public function testCreateAuditCycleRandomlySamplesProducts(): void
    {
        $p1 = new Product();
        $p1->setSku('AUDIT-P1');
        $p1->setStockQuantity(50);

        $p2 = new Product();
        $p2->setSku('AUDIT-P2');
        $p2->setStockQuantity(20);

        $this->productRepository->method('findAll')->willReturn([$p1, $p2]);
        $this->auditCycleRepository->expects($this->once())->method('save');

        $cycle = $this->manager->createAuditCycle(null, 2, 'Routine monthly audit');

        $this->assertEquals(AuditCycle::STATUS_IN_PROGRESS, $cycle->getStatus());
        $this->assertCount(2, $cycle->getDiscrepancies());
        $this->assertEquals('Routine monthly audit', $cycle->getNotes());
    }

    public function testReconcileAuditCyclePostsStockAdjustmentOnVariance(): void
    {
        $product = new Product();
        $product->setSku('AUDIT-VAR');
        $product->setUnitPrice(10.0);
        $product->setStockQuantity(50);

        $ref = new \ReflectionProperty(Product::class, 'id');
        $ref->setValue($product, 99);

        $cycle = new AuditCycle();
        $cycle->setAuditCode('AUD-TEST-001');

        $discrepancy = new AuditDiscrepancy();
        $discrepancy->setProduct($product);
        $discrepancy->setSystemQuantity(50);
        $cycle->addDiscrepancy($discrepancy);

        $this->stockManager->expects($this->once())
            ->method('recordMovement')
            ->with($product, \App\Entity\StockMovement::TYPE_ADJUST, 45); // Physical count was 45

        $this->auditCycleRepository->expects($this->once())->method('save')->with($cycle);

        $this->manager->reconcileAuditCycle($cycle, [
            ['product_id' => 99, 'counted_quantity' => 45]
        ]);

        $this->assertEquals(AuditCycle::STATUS_COMPLETED, $cycle->getStatus());
        $this->assertEquals(45, $discrepancy->getCountedQuantity());
        $this->assertEquals(-5, $discrepancy->getVarianceQuantity());
        $this->assertEquals('-50.00', $discrepancy->getVarianceValue());
        $this->assertTrue($discrepancy->isReconciled());
    }
}
