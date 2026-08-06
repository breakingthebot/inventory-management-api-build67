<?php

// src/Entity/ProductOption.php
// Doctrine Entity representing configurable product attribute options (Color, Size, Material).
// Connects to: src/Repository/ProductOptionRepository.php, src/Entity/ProductVariant.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\ProductOptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductOptionRepository::class)]
#[ORM\Table(name: 'product_options')]
class ProductOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['variant:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Option name cannot be blank.')]
    #[Groups(['variant:read', 'variant:write'])]
    private ?string $name = null; // e.g. "Color", "Size"

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Option code cannot be blank.')]
    #[Groups(['variant:read', 'variant:write'])]
    private ?string $code = null; // e.g. "color", "size"

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['variant:read', 'variant:write'])]
    private array $possibleValues = []; // e.g. ["Red", "Blue", "Green"]

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtolower(trim($code));
        return $this;
    }

    public function getPossibleValues(): array
    {
        return $this->possibleValues;
    }

    public function setPossibleValues(array $possibleValues): self
    {
        $this->possibleValues = array_values(array_unique($possibleValues));
        return $this;
    }
}
