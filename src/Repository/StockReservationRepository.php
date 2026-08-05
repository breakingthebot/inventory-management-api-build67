<?php

// src/Repository/StockReservationRepository.php
// Doctrine Repository for StockReservation entities providing reserved quantity sums and TTL cleanup queries.
// Connects to: src/Entity/StockReservation.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\Product;
use App\Entity\StockReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockReservation>
 */
class StockReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockReservation::class);
    }

    public function save(StockReservation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Calculates total quantity of active reserved (held) stock for a product.
     */
    public function getReservedQuantitySum(Product $product): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.quantity)')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('product', $product)
            ->setParameter('status', StockReservation::STATUS_RESERVED)
            ->setParameter('now', new \DateTimeImmutable());

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ? (int)$result : 0;
    }

    /**
     * Releases expired stock reservations whose TTL has passed.
     */
    public function releaseExpiredReservations(): int
    {
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('r')
            ->update()
            ->set('r.status', ':expiredStatus')
            ->andWhere('r.status = :reservedStatus')
            ->andWhere('r.expiresAt <= :now')
            ->setParameter('expiredStatus', StockReservation::STATUS_EXPIRED)
            ->setParameter('reservedStatus', StockReservation::STATUS_RESERVED)
            ->setParameter('now', $now);

        return $qb->getQuery()->execute();
    }
}
