<?php

// src/Repository/AuditCycleRepository.php
// Doctrine Repository for AuditCycle entities.
// Connects to: src/Entity/AuditCycle.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\AuditCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditCycle>
 */
class AuditCycleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditCycle::class);
    }

    public function save(AuditCycle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
