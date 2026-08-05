<?php

// src/Repository/WebhookSubscriptionRepository.php
// Doctrine Repository for WebhookSubscription entities.
// Connects to: src/Entity/WebhookSubscription.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\WebhookSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookSubscription>
 */
class WebhookSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookSubscription::class);
    }

    public function save(WebhookSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WebhookSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return WebhookSubscription[]
     */
    public function findActiveSubscribers(string $event = 'inventory.low_stock'): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.active = true')
            ->andWhere('w.eventFilter = :event OR w.eventFilter = :all')
            ->setParameter('event', $event)
            ->setParameter('all', '*')
            ->getQuery()
            ->getResult();
    }
}
