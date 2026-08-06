<?php

// src/Entity/EntityRevision.php
// Doctrine Entity representing event-sourced historical entity state snapshots for audit compliance and rollbacks.
// Connects to: src/Repository/EntityRevisionRepository.php, src/Service/AuditTrailEngine.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\EntityRevisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EntityRevisionRepository::class)]
#[ORM\Table(name: 'entity_revisions')]
class EntityRevision
{
    public const ACTION_CREATED = 'CREATED';
    public const ACTION_UPDATED = 'UPDATED';
    public const ACTION_DELETED = 'DELETED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['revision:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['revision:read'])]
    private ?string $entityClass = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['revision:read'])]
    private int $entityId = 0;

    #[ORM\Column(length: 20)]
    #[Groups(['revision:read'])]
    private string $action = self::ACTION_UPDATED;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['revision:read'])]
    private array $payloadSnapshot = [];

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['revision:read'])]
    private ?string $changedBy = null;

    #[ORM\Column]
    #[Groups(['revision:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = strtoupper($action);
        return $this;
    }

    public function getPayloadSnapshot(): array
    {
        return $this->payloadSnapshot;
    }

    public function setPayloadSnapshot(array $payloadSnapshot): self
    {
        $this->payloadSnapshot = $payloadSnapshot;
        return $this;
    }

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }

    public function setChangedBy(?string $changedBy): self
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
