<?php

// src/Entity/StockReservation.php
// Doctrine Entity representing temporary e-commerce cart stock holds with TTL expiration to prevent overselling.
// Connects to: src/Entity/Product.php, src/Repository/StockReservationRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\StockReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StockReservationRepository::class)]
#[ORM\Table(name: 'stock_reservations')]
class StockReservation
{
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_EXPIRED = 'EXPIRED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reservation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['reservation:read'])]
    private ?string $reservationToken = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['reservation:read'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['reservation:read'])]
    private int $quantity = 0;

    #[ORM\Column(length: 20)]
    #[Groups(['reservation:read'])]
    private string $status = self::STATUS_RESERVED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['reservation:read'])]
    private ?string $sessionKey = null;

    #[ORM\Column]
    #[Groups(['reservation:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = (new \DateTimeImmutable())->modify('+15 minutes');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservationToken(): ?string
    {
        return $this->reservationToken;
    }

    public function setReservationToken(string $reservationToken): self
    {
        $this->reservationToken = strtoupper(trim($reservationToken));
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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(0, $quantity);
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

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || ($this->status === self::STATUS_RESERVED && $this->expiresAt <= new \DateTimeImmutable());
    }

    public function getSessionKey(): ?string
    {
        return $this->sessionKey;
    }

    public function setSessionKey(?string $sessionKey): self
    {
        $this->sessionKey = $sessionKey;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
