<?php

// tests/Service/WarehouseManagerTest.php
// Unit tests for WarehouseManager service verifying warehouse stock adjustments, transfers, and global stock rollups.
// Connects to: src/Service/WarehouseManager.php, src/Entity/Warehouse.php, src/Entity/Product.php, src/Entity/WarehouseStock.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\Warehouse;
use App\Entity\WarehouseStock;
use App\Repository\WarehouseStockRepository;
use App\Service\WarehouseManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WarehouseManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private WarehouseStockRepository $warehouseStockRepository;
    private WarehouseManager $warehouseManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->warehouseStockRepository = $this->createMock(WarehouseStockRepository::class);
        $this->warehouseManager = new WarehouseManager(
            $this->entityManager,
            $this->warehouseStockRepository
        );
    }

    public function testRecordWarehouseStockInAndGlobalSync(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH-EAST');
        $warehouse->setName('East Hub');

        $product = new Product();
        $product->setSku('PROD-WH1');
        $product->setStockQuantity(0);

        $warehouseStock = new WarehouseStock();
        $warehouseStock->setWarehouse($warehouse);
        $warehouseStock->setProduct($product);
        $warehouseStock->setStockQuantity(0);

        $this->warehouseStockRepository->method('findOrCreate')->willReturn($warehouseStock);
        $this->warehouseStockRepository->method('getGlobalStockSum')->willReturn(0);

        $this->entityManager->expects($this->atLeast(3))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $movement = $this->warehouseManager->recordWarehouseMovement($warehouse, $product, StockMovement::TYPE_IN, 50);

        $this->assertEquals(50, $warehouseStock->getStockQuantity());
        $this->assertEquals(50, $product->getStockQuantity());
        $this->assertEquals(Product::STATUS_IN_STOCK, $product->getStatus());
        $this->assertEquals(50, $movement->getResultingStock());
    }

    public function testTransferStockBetweenWarehouses(): void
    {
        $source = new Warehouse();
        $source->setCode('WH-EAST');

        $target = new Warehouse();
        $target->setCode('WH-WEST');

        // Give entities dummy IDs via reflection for equality comparison
        $ref = new \ReflectionProperty(Warehouse::class, 'id');
        $ref->setValue($source, 1);
        $ref->setValue($target, 2);

        $product = new Product();
        $product->setSku('PROD-TRANSFER');

        $sourceStock = new WarehouseStock();
        $sourceStock->setWarehouse($source);
        $sourceStock->setProduct($product);
        $sourceStock->setStockQuantity(30);

        $targetStock = new WarehouseStock();
        $targetStock->setWarehouse($target);
        $targetStock->setProduct($product);
        $targetStock->setStockQuantity(5);

        $this->warehouseStockRepository->expects($this->exactly(2))
            ->method('findOrCreate')
            ->willReturnCallback(function ($wh) use ($source, $target, $sourceStock, $targetStock) {
                return $wh->getCode() === 'WH-EAST' ? $sourceStock : $targetStock;
            });

        $result = $this->warehouseManager->transferStock($source, $target, $product, 10, 'Rebalance PO-99');

        $this->assertEquals(20, $sourceStock->getStockQuantity());
        $this->assertEquals(15, $targetStock->getStockQuantity());
        $this->assertEquals(-10, $result['sourceMovement']->getQuantity());
        $this->assertEquals(10, $result['targetMovement']->getQuantity());
    }

    public function testTransferInsufficientStockThrowsException(): void
    {
        $source = new Warehouse();
        $source->setCode('WH-EAST');
        $target = new Warehouse();
        $target->setCode('WH-WEST');

        $ref = new \ReflectionProperty(Warehouse::class, 'id');
        $ref->setValue($source, 1);
        $ref->setValue($target, 2);

        $product = new Product();

        $sourceStock = new WarehouseStock();
        $sourceStock->setStockQuantity(2);

        $this->warehouseStockRepository->method('findOrCreate')->willReturn($sourceStock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock in source warehouse');

        $this->warehouseManager->transferStock($source, $target, $product, 10);
    }
}
