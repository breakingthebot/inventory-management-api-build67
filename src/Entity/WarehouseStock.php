<?php

// src/Entity/WarehouseStock.php
// Doctrine Entity tracking product inventory levels per specific warehouse location.
// Connects to: src/Entity/Warehouse.php, src/Entity/Product.php, src/Repository/WarehouseStockRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\WarehouseStockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WarehouseStockRepository::class)]
#[ORM\Table(name: 'warehouse_stocks')]
#[ORM\UniqueConstraint(name: 'unique_warehouse_product', columns: ['warehouse_id', 'product_id'])]
#[ORM\HasLifecycleCallbacks]
class WarehouseStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['warehousestock:read', 'warehouse:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Warehouse::class, inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['warehousestock:read'])]
    private ?Warehouse $warehouse = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['warehousestock:read', 'warehouse:detail'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Warehouse stock quantity cannot be negative.')]
    #[Groups(['warehousestock:read', 'warehouse:detail'])]
    private int $stockQuantity = 0;

    #[ORM\Column]
    #[Groups(['warehousestock:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['warehousestock:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        $this->stockQuantity = max(0, $stockQuantity);
        return $this;
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
