<?php

// src/Repository/BatchLotRepository.php
// Doctrine Repository for BatchLot entities providing FEFO ordering and expiration threshold queries.
// Connects to: src/Entity/BatchLot.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\BatchLot;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchLot>
 */
class BatchLotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchLot::class);
    }

    public function save(BatchLot $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns non-zero batch lots for a product ordered by Expiration Date ASC (First Expired, First Out - FEFO).
     * @return BatchLot[]
     */
    public function findFefoLots(Product $product): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.product = :product')
            ->andWhere('b.quantity > 0')
            ->setParameter('product', $product)
            ->orderBy('b.expirationDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns batch lots expiring within the specified number of days.
     * @return BatchLot[]
     */
    public function findExpiringLots(int $daysThreshold = 30): array
    {
        $thresholdDate = (new \DateTimeImmutable())->modify(sprintf('+%d days', $daysThreshold));

        return $this->createQueryBuilder('b')
            ->andWhere('b.quantity > 0')
            ->andWhere('b.expirationDate <= :threshold')
            ->setParameter('threshold', $thresholdDate)
            ->orderBy('b.expirationDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
