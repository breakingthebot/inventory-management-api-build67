<?php

// src/Repository/TaxZoneRepository.php
// Doctrine Repository for TaxZone entities.
// Connects to: src/Entity/TaxZone.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\TaxZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxZone>
 */
class TaxZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxZone::class);
    }

    public function save(TaxZone $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
