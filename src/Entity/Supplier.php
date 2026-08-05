<?php

// src/Entity/Supplier.php
// Doctrine Entity representing vendor suppliers providing inventory stock reorders.
// Connects to: src/Entity/PurchaseOrder.php, src/Repository/SupplierRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\SupplierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ORM\Table(name: 'suppliers')]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['supplier:read', 'po:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank(message: 'Supplier code is required.')]
    #[Groups(['supplier:read', 'supplier:write', 'po:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Supplier name is required.')]
    #[Groups(['supplier:read', 'supplier:write', 'po:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'Invalid contact email format.')]
    #[Groups(['supplier:read', 'supplier:write'])]
    private ?string $contactEmail = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Groups(['supplier:read', 'supplier:write'])]
    private int $leadTimeDays = 7;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: PurchaseOrder::class)]
    private Collection $purchaseOrders;

    #[ORM\Column]
    #[Groups(['supplier:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->purchaseOrders = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));
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

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail ? strtolower(trim($contactEmail)) : null;
        return $this;
    }

    public function getLeadTimeDays(): int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(int $leadTimeDays): self
    {
        $this->leadTimeDays = max(0, $leadTimeDays);
        return $this;
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function getPurchaseOrders(): Collection
    {
        return $this->purchaseOrders;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
