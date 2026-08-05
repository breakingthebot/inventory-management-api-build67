<?php

// src/Repository/AuditDiscrepancyRepository.php
// Doctrine Repository for AuditDiscrepancy entities.
// Connects to: src/Entity/AuditDiscrepancy.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\AuditDiscrepancy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditDiscrepancy>
 */
class AuditDiscrepancyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditDiscrepancy::class);
    }

    public function save(AuditDiscrepancy $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
