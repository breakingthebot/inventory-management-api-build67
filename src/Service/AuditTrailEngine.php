<?php

// src/Service/AuditTrailEngine.php
// Domain service recording event-sourced entity revision snapshots and executing state rollbacks.
// Connects to: src/Entity/EntityRevision.php, src/Repository/EntityRevisionRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\EntityRevision;
use App\Repository\EntityRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AuditTrailEngine
{
    public function __construct(
        private readonly EntityRevisionRepository $revisionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface $normalizer
    ) {
    }

    /**
     * Captures a point-in-time revision snapshot of an entity state.
     */
    public function recordRevision(object $entity, string $action, ?string $username = null): EntityRevision
    {
        $className = get_class($entity);
        $entityId = method_exists($entity, 'getId') ? (int)$entity->getId() : 0;

        try {
            $snapshot = $this->normalizer->normalize($entity, 'array', ['groups' => ['product:read', 'user:read', 'warehouse:read', 'po:read']]);
        } catch (\Throwable) {
            $snapshot = ['raw' => (string)$entity];
        }

        $revision = new EntityRevision();
        $revision->setEntityClass($className);
        $revision->setEntityId($entityId);
        $revision->setAction($action);
        $revision->setPayloadSnapshot($snapshot);
        $revision->setChangedBy($username ?? 'system');

        $this->revisionRepository->save($revision, true);

        return $revision;
    }

    /**
     * Retrieves revision history timeline for an entity.
     * @return EntityRevision[]
     */
    public function getRevisionHistory(string $entityClass, int $entityId): array
    {
        return $this->revisionRepository->findByEntity($entityClass, $entityId);
    }

    /**
     * Rolls back an entity to a previous revision state snapshot.
     */
    public function rollbackEntityToRevision(int $revisionId): object
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision) {
            throw new \InvalidArgumentException('Entity revision snapshot not found.');
        }

        $className = $revision->getEntityClass();
        $entityId = $revision->getEntityId();

        $entity = $this->entityManager->find($className, $entityId);
        if (!$entity) {
            throw new \InvalidArgumentException(sprintf('Target entity of type "%s" with ID %d not found.', $className, $entityId));
        }

        $snapshot = $revision->getPayloadSnapshot();
        foreach ($snapshot as $key => $val) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($entity, $setter) && !is_array($val) && !is_object($val)) {
                try {
                    $entity->$setter($val);
                } catch (\Throwable) {
                    // Ignore non-scalar setter errors
                }
            }
        }

        $this->entityManager->flush();

        // Record rollback operation in revision history
        $this->recordRevision($entity, EntityRevision::ACTION_UPDATED, 'rollback_engine');

        return $entity;
    }
}
