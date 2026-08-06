<?php

// tests/Service/BackorderManagerTest.php
// Unit tests for BackorderManager service verifying FIFO backorder queueing and restock allocation.
// Connects to: src/Service/BackorderManager.php, src/Entity/Backorder.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Backorder;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Repository\BackorderRepository;
use App\Service\BackorderManager;
use App\Service\StockManager;
use PHPUnit\Framework\TestCase;

class BackorderManagerTest extends TestCase
{
    private BackorderRepository $backorderRepository;
    private StockManager $stockManager;
    private BackorderManager $manager;

    protected function setUp(): void
    {
        $this->backorderRepository = $this->createMock(BackorderRepository::class);
        $this->stockManager = $this->createMock(StockManager::class);

        $this->manager = new BackorderManager(
            $this->backorderRepository,
            $this->stockManager
        );
    }

    public function testCreateBackorderEnqueuesRequest(): void
    {
        $product = new Product();
        $product->setSku('BO-ITEM-001');

        $this->backorderRepository->expects($this->once())->method('save');

        $bo = $this->manager->createBackorder($product, 'customer@domain.com', 2);

        $this->assertEquals(Backorder::STATUS_PENDING, $bo->getStatus());
        $this->assertEquals('customer@domain.com', $bo->getCustomerEmail());
        $this->assertEquals(2, $bo->getQuantity());
        $this->assertStringStartsWith('BO-', $bo->getBackorderNumber());
    }

    public function testAllocateStockToBackordersFulfillsInFifoOrder(): void
    {
        $product = new Product();
        $product->setStockQuantity(10); // 10 units available

        $bo1 = new Backorder();
        $bo1->setProduct($product);
        $bo1->setQuantity(4);
        $bo1->setBackorderNumber('BO-001');

        $bo2 = new Backorder();
        $bo2->setProduct($product);
        $bo2->setQuantity(5);
        $bo2->setBackorderNumber('BO-002');

        $this->backorderRepository->method('findPendingBackordersForProduct')->with($product)->willReturn([$bo1, $bo2]);

        $this->stockManager->expects($this->exactly(2))
            ->method('recordMovement')
            ->willReturnCallback(function ($p, $type, $qty) {
                $this->assertEquals(StockMovement::TYPE_OUT, $type);
                $sm = new StockMovement();
                $sm->setQuantity($qty);
                return $sm;
            });

        $fulfilled = $this->manager->allocateStockToBackorders($product);

        $this->assertCount(2, $fulfilled);
        $this->assertEquals(Backorder::STATUS_FULFILLED, $bo1->getStatus());
        $this->assertEquals(Backorder::STATUS_FULFILLED, $bo2->getStatus());
    }
}
