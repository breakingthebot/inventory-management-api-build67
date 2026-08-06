<?php

// src/Entity/ProductVariant.php
// Doctrine Entity representing child SKU variants under a parent Product catalog item.
// Connects to: src/Entity/Product.php, src/Repository/ProductVariantRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\ProductVariantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[ORM\Table(name: 'product_variants')]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['variant:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['variant:read'])]
    private ?Product $parentProduct = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Variant SKU cannot be blank.')]
    #[Groups(['variant:read', 'variant:write', 'product:read'])]
    private ?string $sku = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['variant:read', 'variant:write', 'product:read'])]
    private array $optionValues = []; // e.g. {"color": "Red", "size": "XL"}

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['variant:read', 'variant:write', 'product:read'])]
    private ?string $priceOverride = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['variant:read', 'variant:write', 'product:read'])]
    private int $stockQuantity = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['variant:read', 'variant:write'])]
    private int $minStockLevel = 5;

    #[ORM\Column(length: 20)]
    #[Groups(['variant:read', 'product:read'])]
    private string $status = 'IN_STOCK';

    #[ORM\Column]
    #[Groups(['variant:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentProduct(): ?Product
    {
        return $this->parentProduct;
    }

    public function setParentProduct(?Product $parentProduct): self
    {
        $this->parentProduct = $parentProduct;
        return $this;
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

    public function getOptionValues(): array
    {
        return $this->optionValues;
    }

    public function setOptionValues(array $optionValues): self
    {
        $this->optionValues = $optionValues;
        return $this;
    }

    public function getPriceOverride(): ?string
    {
        return $this->priceOverride;
    }

    public function setPriceOverride(?string $priceOverride): self
    {
        $this->priceOverride = $priceOverride !== null ? number_format((float)$priceOverride, 2, '.', '') : null;
        return $this;
    }

    public function getEffectivePrice(): float
    {
        if ($this->priceOverride !== null) {
            return (float)$this->priceOverride;
        }

        return $this->parentProduct ? (float)$this->parentProduct->getUnitPrice() : 0.0;
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

    public function recalculateStatus(): void
    {
        if ($this->stockQuantity <= 0) {
            $this->status = 'OUT_OF_STOCK';
        } elseif ($this->stockQuantity <= $this->minStockLevel) {
            $this->status = 'LOW_STOCK';
        } else {
            $this->status = 'IN_STOCK';
        }
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
