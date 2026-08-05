<?php

// src/Entity/AuditCycle.php
// Doctrine Entity representing periodic physical inventory audit sampling sessions.
// Connects to: src/Entity/Warehouse.php, src/Entity/AuditDiscrepancy.php, src/Repository/AuditCycleRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\AuditCycleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AuditCycleRepository::class)]
#[ORM\Table(name: 'audit_cycles')]
class AuditCycle
{
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['audit:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['audit:read'])]
    private ?string $auditCode = null;

    #[ORM\ManyToOne(targetEntity: Warehouse::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read'])]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(length: 30)]
    #[Groups(['audit:read'])]
    private string $status = self::STATUS_IN_PROGRESS;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'auditCycle', targetEntity: AuditDiscrepancy::class, cascade: ['persist', 'remove'])]
    #[Groups(['audit:detail'])]
    private Collection $discrepancies;

    #[ORM\Column]
    #[Groups(['audit:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['audit:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->discrepancies = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditCode(): ?string
    {
        return $this->auditCode;
    }

    public function setAuditCode(string $auditCode): self
    {
        $this->auditCode = strtoupper(trim($auditCode));
        return $this;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): self
    {
        $this->warehouse = $warehouse;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        if ($status === self::STATUS_COMPLETED && $this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Collection<int, AuditDiscrepancy>
     */
    public function getDiscrepancies(): Collection
    {
        return $this->discrepancies;
    }

    public function addDiscrepancy(AuditDiscrepancy $discrepancy): self
    {
        if (!$this->discrepancies->contains($discrepancy)) {
            $this->discrepancies->add($discrepancy);
            $discrepancy->setAuditCycle($this);
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
