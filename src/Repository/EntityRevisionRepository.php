<?php

// src/Repository/EntityRevisionRepository.php
// Doctrine Repository for EntityRevision entities.
// Connects to: src/Entity/EntityRevision.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\EntityRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntityRevision>
 */
class EntityRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntityRevision::class);
    }

    public function save(EntityRevision $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds historical revisions for a specific entity class and ID.
     * @return EntityRevision[]
     */
    public function findByEntity(string $entityClass, int $entityId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.entityClass = :entityClass')
            ->andWhere('r.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
