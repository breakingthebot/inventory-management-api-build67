<?php

// src/Entity/BatchLot.php
// Doctrine Entity representing manufacturing batch lots with expiration dates for FEFO inventory management.
// Connects to: src/Entity/Product.php, src/Repository/BatchLotRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\BatchLotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchLotRepository::class)]
#[ORM\Table(name: 'batch_lots')]
class BatchLot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lot:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['lot:read'])]
    private ?Product $product = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Batch number is required.')]
    #[Groups(['lot:read', 'lot:write'])]
    private ?string $batchNumber = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Groups(['lot:read', 'lot:write'])]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['lot:read', 'lot:write'])]
    private ?\DateTimeImmutable $manufacturingDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'Expiration date is required.')]
    #[Groups(['lot:read', 'lot:write'])]
    private ?\DateTimeImmutable $expirationDate = null;

    #[ORM\Column]
    #[Groups(['lot:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBatchNumber(): ?string
    {
        return $this->batchNumber;
    }

    public function setBatchNumber(string $batchNumber): self
    {
        $this->batchNumber = strtoupper(trim($batchNumber));
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(0, $quantity);
        return $this;
    }

    public function getManufacturingDate(): ?\DateTimeImmutable
    {
        return $this->manufacturingDate;
    }

    public function setManufacturingDate(?\DateTimeImmutable $manufacturingDate): self
    {
        $this->manufacturingDate = $manufacturingDate;
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeImmutable $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expirationDate !== null && $this->expirationDate < new \DateTimeImmutable();
    }

    public function getDaysUntilExpiration(): int
    {
        if ($this->expirationDate === null) {
            return 99999;
        }
        $now = new \DateTimeImmutable('today');
        $exp = $this->expirationDate->setTime(0, 0);
        return (int)$now->diff($exp)->format('%r%a');
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
