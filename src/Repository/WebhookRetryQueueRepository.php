<?php

// src/Repository/WebhookRetryQueueRepository.php
// Doctrine Repository for WebhookRetryQueue entities providing due retry queries.
// Connects to: src/Entity/WebhookRetryQueue.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\WebhookRetryQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookRetryQueue>
 */
class WebhookRetryQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookRetryQueue::class);
    }

    public function save(WebhookRetryQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds pending webhook retries where nextAttemptAt is less than or equal to current time.
     * @return WebhookRetryQueue[]
     */
    public function findDueRetries(int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.nextAttemptAt <= :now')
            ->setParameter('status', WebhookRetryQueue::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('r.nextAttemptAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
