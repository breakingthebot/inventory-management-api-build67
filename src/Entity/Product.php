<?php

// src/Entity/Product.php
// Doctrine ORM Entity representing Inventory Products with stock quantity, pricing, and status.
// Connects to: src/Entity/Category.php, src/Entity/StockMovement.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    public const STATUS_IN_STOCK = 'IN_STOCK';
    public const STATUS_LOW_STOCK = 'LOW_STOCK';
    public const STATUS_OUT_OF_STOCK = 'OUT_OF_STOCK';
    public const STATUS_DISCONTINUED = 'DISCONTINUED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'movement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Product SKU cannot be blank.')]
    #[Assert\Length(max: 50, maxMessage: 'SKU cannot exceed 50 characters.')]
    #[Groups(['product:read', 'product:write', 'movement:read'])]
    private ?string $sku = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Product name cannot be blank.')]
    #[Assert\Length(max: 150, maxMessage: 'Product name cannot exceed 150 characters.')]
    #[Groups(['product:read', 'product:write', 'movement:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Unit price is required.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Price cannot be negative.')]
    #[Groups(['product:read', 'product:write'])]
    private ?string $unitPrice = '0.00';

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: 'Stock quantity is required.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Stock quantity cannot be negative.')]
    #[Groups(['product:read', 'product:write'])]
    private int $stockQuantity = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Minimum stock level cannot be negative.')]
    #[Groups(['product:read', 'product:write'])]
    private int $minStockLevel = 5;

    #[ORM\Column(length: 30)]
    #[Groups(['product:read'])]
    private string $status = self::STATUS_OUT_OF_STOCK;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['product:read', 'product:write'])]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: StockMovement::class, cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups(['product:detail'])]
    private Collection $stockMovements;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->stockMovements = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->recalculateStatus();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recalculateStatus(): void
    {
        if ($this->status === self::STATUS_DISCONTINUED) {
            return;
        }

        if ($this->stockQuantity <= 0) {
            $this->status = self::STATUS_OUT_OF_STOCK;
        } elseif ($this->stockQuantity <= $this->minStockLevel) {
            $this->status = self::STATUS_LOW_STOCK;
        } else {
            $this->status = self::STATUS_IN_STOCK;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = strtoupper(trim($sku));
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string|float $unitPrice): self
    {
        $this->unitPrice = number_format((float)$unitPrice, 2, '.', '');
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        $this->stockQuantity = max(0, $stockQuantity);
        $this->recalculateStatus();
        return $this;
    }

    public function getMinStockLevel(): int
    {
        return $this->minStockLevel;
    }

    public function setMinStockLevel(int $minStockLevel): self
    {
        $this->minStockLevel = max(0, $minStockLevel);
        $this->recalculateStatus();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function getStockMovements(): Collection
    {
        return $this->stockMovements;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
