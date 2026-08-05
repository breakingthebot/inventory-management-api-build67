<?php

// tests/Service/StockManagerTest.php
// Unit tests for StockManager service verifying stock transactions, boundary rules, and exception handling.
// Connects to: src/Service/StockManager.php, src/Entity/Product.php, src/Entity/StockMovement.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Service\StockManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class StockManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private StockManager $stockManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stockManager = new StockManager($this->entityManager);
    }

    public function testRecordStockIn(): void
    {
        $product = new Product();
        $product->setSku('TEST-001');
        $product->setName('Test Widget');
        $product->setStockQuantity(5);
        $product->setMinStockLevel(10);

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $movement = $this->stockManager->recordMovement($product, StockMovement::TYPE_IN, 15, 'Shipment arrival');

        $this->assertEquals(20, $product->getStockQuantity());
        $this->assertEquals(Product::STATUS_IN_STOCK, $product->getStatus());
        $this->assertEquals(StockMovement::TYPE_IN, $movement->getType());
        $this->assertEquals(20, $movement->getResultingStock());
    }

    public function testRecordStockOut(): void
    {
        $product = new Product();
        $product->setSku('TEST-002');
        $product->setName('Test Gadget');
        $product->setStockQuantity(20);
        $product->setMinStockLevel(10);

        $this->entityManager->expects($this->exactly(2))->method('persist');

        $movement = $this->stockManager->recordMovement($product, StockMovement::TYPE_OUT, 12, 'Customer order #101');

        $this->assertEquals(8, $product->getStockQuantity());
        $this->assertEquals(Product::STATUS_LOW_STOCK, $product->getStatus());
        $this->assertEquals(8, $movement->getResultingStock());
    }

    public function testRecordStockOutInsufficientStockThrowsException(): void
    {
        $product = new Product();
        $product->setStockQuantity(5);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->stockManager->recordMovement($product, StockMovement::TYPE_OUT, 10);
    }

    public function testRecordStockAdjust(): void
    {
        $product = new Product();
        $product->setStockQuantity(100);

        $movement = $this->stockManager->recordMovement($product, StockMovement::TYPE_ADJUST, 0, 'Audit correction');

        $this->assertEquals(0, $product->getStockQuantity());
        $this->assertEquals(Product::STATUS_OUT_OF_STOCK, $product->getStatus());
    }
}
