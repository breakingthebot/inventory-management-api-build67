<?php

// src/Entity/Warehouse.php
// Doctrine Entity representing physical warehouse facilities and fulfillment hubs.
// Connects to: src/Entity/WarehouseStock.php, src/Repository/WarehouseRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\WarehouseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WarehouseRepository::class)]
#[ORM\Table(name: 'warehouses')]
class Warehouse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['warehouse:read', 'movement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank(message: 'Warehouse code is required.')]
    #[Assert\Length(max: 30, maxMessage: 'Warehouse code cannot exceed 30 characters.')]
    #[Groups(['warehouse:read', 'warehouse:write', 'movement:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Warehouse name is required.')]
    #[Groups(['warehouse:read', 'warehouse:write', 'movement:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['warehouse:read', 'warehouse:write'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['warehouse:read', 'warehouse:write'])]
    private bool $active = true;

    #[ORM\OneToMany(mappedBy: 'warehouse', targetEntity: WarehouseStock::class, cascade: ['remove'])]
    private Collection $stocks;

    #[ORM\Column]
    #[Groups(['warehouse:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, WarehouseStock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
