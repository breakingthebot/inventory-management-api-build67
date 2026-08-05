<?php

// src/Entity/Tenant.php
// Doctrine Entity representing multi-tenant business organizations operating on the inventory platform.
// Connects to: src/Repository/TenantRepository.php, src/Entity/User.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
class Tenant
{
    public const PLAN_FREE = 'FREE';
    public const PLAN_PRO = 'PRO';
    public const PLAN_ENTERPRISE = 'ENTERPRISE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tenant:read', 'user:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank(message: 'Tenant code is required.')]
    #[Groups(['tenant:read', 'tenant:write', 'user:read', 'product:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Tenant name is required.')]
    #[Groups(['tenant:read', 'tenant:write', 'user:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 30)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $plan = self::PLAN_PRO;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private bool $active = true;

    #[ORM\Column]
    #[Groups(['tenant:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
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

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function setPlan(string $plan): self
    {
        $this->plan = strtoupper($plan);
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
