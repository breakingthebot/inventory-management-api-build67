<?php

// src/Entity/StockMovement.php
// Doctrine ORM Entity logging inventory stock transactions (IN, OUT, ADJUST).
// Connects to: src/Entity/Product.php, src/Repository/StockMovementRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\StockMovementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
#[ORM\Table(name: 'stock_movements')]
class StockMovement
{
    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_ADJUST = 'ADJUST';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['movement:read', 'product:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'stockMovements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['movement:read'])]
    private ?Product $product = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::TYPE_IN, self::TYPE_OUT, self::TYPE_ADJUST])]
    #[Groups(['movement:read', 'movement:write', 'product:detail'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\NotEqualTo(value: 0, message: 'Quantity change cannot be 0.')]
    #[Groups(['movement:read', 'movement:write', 'product:detail'])]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['movement:read', 'product:detail'])]
    private ?int $resultingStock = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movement:read', 'movement:write', 'product:detail'])]
    private ?string $reason = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['movement:read', 'movement:write', 'product:detail'])]
    private ?string $reference = null;

    #[ORM\Column]
    #[Groups(['movement:read', 'product:detail'])]
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = strtoupper($type);
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getResultingStock(): ?int
    {
        return $this->resultingStock;
    }

    public function setResultingStock(int $resultingStock): self
    {
        $this->resultingStock = $resultingStock;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
