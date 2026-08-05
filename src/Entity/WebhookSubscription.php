<?php

// src/Entity/WebhookSubscription.php
// Doctrine Entity representing registered outbound Webhook subscribers for inventory alert events.
// Connects to: src/Repository/WebhookSubscriptionRepository.php, src/Service/NotificationService.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\WebhookSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WebhookSubscriptionRepository::class)]
#[ORM\Table(name: 'webhook_subscriptions')]
class WebhookSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['webhook:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Webhook URL cannot be blank.')]
    #[Assert\Url(message: 'Invalid Webhook URL format.')]
    #[Groups(['webhook:read', 'webhook:write'])]
    private ?string $url = null;

    #[ORM\Column(length: 64)]
    #[Groups(['webhook:read'])]
    private ?string $secret = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['webhook:read', 'webhook:write'])]
    private bool $active = true;

    #[ORM\Column(length: 100)]
    #[Groups(['webhook:read', 'webhook:write'])]
    private string $eventFilter = 'inventory.low_stock';

    #[ORM\Column]
    #[Groups(['webhook:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->secret = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = trim($url);
        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;
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

    public function getEventFilter(): string
    {
        return $this->eventFilter;
    }

    public function setEventFilter(string $eventFilter): self
    {
        $this->eventFilter = $eventFilter;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
