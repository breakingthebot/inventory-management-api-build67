<?php

// src/Entity/AuditDiscrepancy.php
// Doctrine Entity representing item sampling counts and variance adjustments during audit reconciliation.
// Connects to: src/Entity/AuditCycle.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\AuditDiscrepancyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AuditDiscrepancyRepository::class)]
#[ORM\Table(name: 'audit_discrepancies')]
class AuditDiscrepancy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['audit:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AuditCycle::class, inversedBy: 'discrepancies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditCycle $auditCycle = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['audit:detail'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['audit:detail'])]
    private int $systemQuantity = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['audit:detail'])]
    private ?int $countedQuantity = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['audit:detail'])]
    private ?int $varianceQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Groups(['audit:detail'])]
    private ?string $varianceValue = '0.00';

    #[ORM\Column]
    #[Groups(['audit:detail'])]
    private bool $reconciled = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuditCycle(): ?AuditCycle
    {
        return $this->auditCycle;
    }

    public function setAuditCycle(?AuditCycle $auditCycle): self
    {
        $this->auditCycle = $auditCycle;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getSystemQuantity(): int
    {
        return $this->systemQuantity;
    }

    public function setSystemQuantity(int $systemQuantity): self
    {
        $this->systemQuantity = max(0, $systemQuantity);
        return $this;
    }

    public function getCountedQuantity(): ?int
    {
        return $this->countedQuantity;
    }

    public function setCountedQuantity(?int $countedQuantity): self
    {
        $this->countedQuantity = $countedQuantity !== null ? max(0, $countedQuantity) : null;
        $this->recalculateVariance();
        return $this;
    }

    public function getVarianceQuantity(): ?int
    {
        return $this->varianceQuantity;
    }

    public function getVarianceValue(): ?string
    {
        return $this->varianceValue;
    }

    public function isReconciled(): bool
    {
        return $this->reconciled;
    }

    public function setReconciled(bool $reconciled): self
    {
        $this->reconciled = $reconciled;
        return $this;
    }

    public function recalculateVariance(): void
    {
        if ($this->countedQuantity === null) {
            $this->varianceQuantity = null;
            $this->varianceValue = '0.00';
            return;
        }

        $variance = $this->countedQuantity - $this->systemQuantity;
        $this->varianceQuantity = $variance;

        $unitPrice = $this->product ? (float)$this->product->getUnitPrice() : 0.0;
        $val = $variance * $unitPrice;
        $this->varianceValue = number_format($val, 2, '.', '');
    }
}
