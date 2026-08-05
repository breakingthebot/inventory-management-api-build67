<?php

// tests/Service/BatchLotManagerTest.php
// Unit tests for BatchLotManager service verifying FEFO (First Expired, First Out) picking logic and stock allocation.
// Connects to: src/Service/BatchLotManager.php, src/Entity/BatchLot.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\BatchLot;
use App\Entity\Product;
use App\Repository\BatchLotRepository;
use App\Service\BatchLotManager;
use App\Service\StockManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BatchLotManagerTest extends TestCase
{
    private BatchLotRepository $batchLotRepository;
    private StockManager $stockManager;
    private EntityManagerInterface $entityManager;
    private BatchLotManager $manager;

    protected function setUp(): void
    {
        $this->batchLotRepository = $this->createMock(BatchLotRepository::class);
        $this->stockManager = $this->createMock(StockManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->manager = new BatchLotManager(
            $this->batchLotRepository,
            $this->stockManager,
            $this->entityManager
        );
    }

    public function testFefoAllocationPicksEarliestExpiringLotFirst(): void
    {
        $product = new Product();
        $product->setSku('PERISH-001');

        $lot1 = new BatchLot();
        $lot1->setBatchNumber('LOT-EARLY');
        $lot1->setQuantity(10);
        $lot1->setExpirationDate(new \DateTimeImmutable('+10 days'));

        $lot2 = new BatchLot();
        $lot2->setBatchNumber('LOT-LATER');
        $lot2->setQuantity(20);
        $lot2->setExpirationDate(new \DateTimeImmutable('+30 days'));

        $this->batchLotRepository->method('findFefoLots')->with($product)->willReturn([$lot1, $lot2]);

        $this->stockManager->expects($this->once())
            ->method('recordMovement')
            ->with($product, \App\Entity\StockMovement::TYPE_OUT, 15);

        $allocations = $this->manager->allocateFefoStock($product, 15);

        $this->assertCount(2, $allocations);
        $this->assertEquals('LOT-EARLY', $allocations[0]['batch_number']);
        $this->assertEquals(10, $allocations[0]['quantity_deducted']);
        $this->assertEquals(0, $allocations[0]['remaining_lot_stock']);

        $this->assertEquals('LOT-LATER', $allocations[1]['batch_number']);
        $this->assertEquals(5, $allocations[1]['quantity_deducted']);
        $this->assertEquals(15, $allocations[1]['remaining_lot_stock']);
    }
}
