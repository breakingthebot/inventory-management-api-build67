<?php

// src/Repository/WarehouseStockRepository.php
// Doctrine Repository for WarehouseStock entities.
// Connects to: src/Entity/WarehouseStock.php, src/Entity/Warehouse.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Warehouse;
use App\Entity\WarehouseStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseStock>
 */
class WarehouseStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseStock::class);
    }

    public function save(WarehouseStock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrCreate(Warehouse $warehouse, Product $product): WarehouseStock
    {
        $stock = $this->findOneBy(['warehouse' => $warehouse, 'product' => $product]);
        if (!$stock) {
            $stock = new WarehouseStock();
            $stock->setWarehouse($warehouse);
            $stock->setProduct($product);
            $stock->setStockQuantity(0);
        }
        return $stock;
    }

    public function getGlobalStockSum(Product $product): int
    {
        $result = $this->createQueryBuilder('ws')
            ->select('SUM(ws.stockQuantity)')
            ->where('ws.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)($result ?? 0);
    }
}
