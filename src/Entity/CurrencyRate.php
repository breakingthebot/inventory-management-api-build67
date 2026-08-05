<?php

// src/Entity/CurrencyRate.php
// Doctrine Entity representing exchange rates relative to base currency (USD).
// Connects to: src/Repository/CurrencyRateRepository.php, src/Service/CurrencyConverter.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\CurrencyRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
#[ORM\Table(name: 'currency_rates')]
class CurrencyRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['currency:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['currency:read', 'currency:write'])]
    private ?string $code = null;

    #[ORM\Column(length: 10)]
    #[Groups(['currency:read', 'currency:write'])]
    private string $symbol = '$';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    #[Assert\GreaterThan(value: 0)]
    #[Groups(['currency:read', 'currency:write'])]
    private string $rateToBase = '1.000000';

    #[ORM\Column]
    #[Groups(['currency:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getRateToBase(): string
    {
        return $this->rateToBase;
    }

    public function setRateToBase(string|float $rateToBase): self
    {
        $this->rateToBase = number_format((float)$rateToBase, 6, '.', '');
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
