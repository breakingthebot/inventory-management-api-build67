<?php

// src/Entity/TaxZone.php
// Doctrine Entity representing regional tax zones and tax rate percentages.
// Connects to: src/Repository/TaxZoneRepository.php, src/Service/CurrencyConverter.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\TaxZoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaxZoneRepository::class)]
#[ORM\Table(name: 'tax_zones')]
class TaxZone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tax:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['tax:read', 'tax:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['tax:read', 'tax:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Groups(['tax:read', 'tax:write'])]
    private string $ratePercent = '0.00';

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

    public function getRatePercent(): string
    {
        return $this->ratePercent;
    }

    public function setRatePercent(string|float $ratePercent): self
    {
        $this->ratePercent = number_format((float)$ratePercent, 2, '.', '');
        return $this;
    }
}
