<?php

// src/Repository/SupplierMetricsRepository.php
// Doctrine Repository for SupplierMetrics entities.
// Connects to: src/Entity/SupplierMetrics.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\SupplierMetrics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplierMetrics>
 */
class SupplierMetricsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierMetrics::class);
    }

    public function save(SupplierMetrics $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Ranks suppliers by fulfillment accuracy descending and average lead time ascending.
     * @return SupplierMetrics[]
     */
    public function findTopPerformers(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.fulfillmentAccuracyPercentage', 'DESC')
            ->addOrderBy('m.averageLeadTimeDays', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
