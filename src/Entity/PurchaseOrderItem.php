<?php

// src/Entity/PurchaseOrderItem.php
// Doctrine Entity representing line items in a Purchase Order.
// Connects to: src/Entity/PurchaseOrder.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\PurchaseOrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PurchaseOrderItemRepository::class)]
#[ORM\Table(name: 'purchase_order_items')]
class PurchaseOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['po:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PurchaseOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PurchaseOrder $purchaseOrder = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['po:detail'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['po:detail'])]
    private int $quantityOrdered = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['po:detail'])]
    private int $quantityReceived = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['po:detail'])]
    private string $unitCost = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['po:detail'])]
    private string $subtotal = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchaseOrder(): ?PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(?PurchaseOrder $purchaseOrder): self
    {
        $this->purchaseOrder = $purchaseOrder;
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

    public function getQuantityOrdered(): int
    {
        return $this->quantityOrdered;
    }

    public function setQuantityOrdered(int $quantityOrdered): self
    {
        $this->quantityOrdered = max(0, $quantityOrdered);
        $this->recalculateSubtotal();
        return $this;
    }

    public function getQuantityReceived(): int
    {
        return $this->quantityReceived;
    }

    public function setQuantityReceived(int $quantityReceived): self
    {
        $this->quantityReceived = max(0, $quantityReceived);
        return $this;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function setUnitCost(string|float $unitCost): self
    {
        $this->unitCost = number_format((float)$unitCost, 2, '.', '');
        $this->recalculateSubtotal();
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    private function recalculateSubtotal(): void
    {
        $sub = $this->quantityOrdered * (float)$this->unitCost;
        $this->subtotal = number_format($sub, 2, '.', '');
    }
}
