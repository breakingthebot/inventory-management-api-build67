<?php

// src/Entity/PurchaseOrder.php
// Doctrine Entity representing Supplier Purchase Orders with state tracking (DRAFT, SUBMITTED, RECEIVED, CANCELLED).
// Connects to: src/Entity/Supplier.php, src/Entity/PurchaseOrderItem.php, src/Repository/PurchaseOrderRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\PurchaseOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PurchaseOrderRepository::class)]
#[ORM\Table(name: 'purchase_orders')]
#[ORM\HasLifecycleCallbacks]
class PurchaseOrder
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['po:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['po:read'])]
    private ?string $poNumber = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['po:read'])]
    private ?Supplier $supplier = null;

    #[ORM\Column(length: 30)]
    #[Groups(['po:read'])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['po:read'])]
    private string $totalAmount = '0.00';

    #[ORM\OneToMany(mappedBy: 'purchaseOrder', targetEntity: PurchaseOrderItem::class, cascade: ['persist', 'remove'])]
    #[Groups(['po:detail'])]
    private Collection $items;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['po:read'])]
    private ?\DateTimeImmutable $expectedDeliveryDate = null;

    #[ORM\Column]
    #[Groups(['po:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['po:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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

    public function getPoNumber(): ?string
    {
        return $this->poNumber;
    }

    public function setPoNumber(string $poNumber): self
    {
        $this->poNumber = strtoupper(trim($poNumber));
        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function recalculateTotal(): void
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += (float)$item->getSubtotal();
        }
        $this->totalAmount = number_format($sum, 2, '.', '');
    }

    /**
     * @return Collection<int, PurchaseOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PurchaseOrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setPurchaseOrder($this);
            $this->recalculateTotal();
        }
        return $this;
    }

    public function getExpectedDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->expectedDeliveryDate;
    }

    public function setExpectedDeliveryDate(?\DateTimeImmutable $expectedDeliveryDate): self
    {
        $this->expectedDeliveryDate = $expectedDeliveryDate;
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
