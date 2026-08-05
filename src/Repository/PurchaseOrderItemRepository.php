<?php

// src/Repository/PurchaseOrderItemRepository.php
// Doctrine Repository for PurchaseOrderItem entities.
// Connects to: src/Entity/PurchaseOrderItem.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\PurchaseOrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseOrderItem>
 */
class PurchaseOrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrderItem::class);
    }

    public function save(PurchaseOrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
