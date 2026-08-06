<?php

// src/Repository/BackorderRepository.php
// Doctrine Repository for Backorder entities providing FIFO queue queries.
// Connects to: src/Entity/Backorder.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\Backorder;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Backorder>
 */
class BackorderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Backorder::class);
    }

    public function save(Backorder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds pending backorders for a product ordered by creation timestamp ascending (FIFO order).
     * @return Backorder[]
     */
    public function findPendingBackordersForProduct(Product $product): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.product = :product')
            ->andWhere('b.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', Backorder::STATUS_PENDING)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
