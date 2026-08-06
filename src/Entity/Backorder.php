<?php

// src/Entity/Backorder.php
// Doctrine Entity representing customer backorder requests queued in FIFO order for out-of-stock items.
// Connects to: src/Entity/Product.php, src/Repository/BackorderRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\BackorderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BackorderRepository::class)]
#[ORM\Table(name: 'backorders')]
class Backorder
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_FULFILLED = 'FULFILLED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['backorder:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['backorder:read'])]
    private ?string $backorderNumber = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['backorder:read'])]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Customer email is required.')]
    #[Assert\Email(message: 'Invalid email address format.')]
    #[Groups(['backorder:read', 'backorder:write'])]
    private ?string $customerEmail = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThan(value: 0, message: 'Backorder quantity must be greater than zero.')]
    #[Groups(['backorder:read', 'backorder:write'])]
    private int $quantity = 1;

    #[ORM\Column(length: 20)]
    #[Groups(['backorder:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    #[Groups(['backorder:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['backorder:read'])]
    private ?\DateTimeImmutable $fulfilledAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBackorderNumber(): ?string
    {
        return $this->backorderNumber;
    }

    public function setBackorderNumber(string $backorderNumber): self
    {
        $this->backorderNumber = strtoupper(trim($backorderNumber));
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

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = strtolower(trim($customerEmail));
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(1, $quantity);
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        if ($status === self::STATUS_FULFILLED && $this->fulfilledAt === null) {
            $this->fulfilledAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFulfilledAt(): ?\DateTimeImmutable
    {
        return $this->fulfilledAt;
    }
}
