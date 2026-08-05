<?php

// src/Entity/WebhookRetryQueue.php
// Doctrine Entity tracking failed HTTP webhook dispatches for asynchronous exponential backoff retries.
// Connects to: src/Entity/WebhookSubscription.php, src/Repository/WebhookRetryQueueRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\WebhookRetryQueueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WebhookRetryQueueRepository::class)]
#[ORM\Table(name: 'webhook_retry_queue')]
class WebhookRetryQueue
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_FAILED = 'FAILED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['webhook_retry:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WebhookSubscription::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['webhook_retry:read'])]
    private ?WebhookSubscription $subscription = null;

    #[ORM\Column(length: 50)]
    #[Groups(['webhook_retry:read'])]
    private ?string $eventName = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['webhook_retry:read'])]
    private array $payload = [];

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['webhook_retry:read'])]
    private int $attemptCount = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['webhook_retry:read'])]
    private int $maxAttempts = 5;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['webhook_retry:read'])]
    private \DateTimeImmutable $nextAttemptAt;

    #[ORM\Column(length: 20)]
    #[Groups(['webhook_retry:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['webhook_retry:read'])]
    private ?string $lastErrorMessage = null;

    #[ORM\Column]
    #[Groups(['webhook_retry:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->nextAttemptAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): ?WebhookSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(?WebhookSubscription $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    public function setAttemptCount(int $attemptCount): self
    {
        $this->attemptCount = max(0, $attemptCount);
        return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    public function getNextAttemptAt(): \DateTimeImmutable
    {
        return $this->nextAttemptAt;
    }

    public function setNextAttemptAt(\DateTimeImmutable $nextAttemptAt): self
    {
        $this->nextAttemptAt = $nextAttemptAt;
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

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }

    public function setLastErrorMessage(?string $lastErrorMessage): self
    {
        $this->lastErrorMessage = $lastErrorMessage;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
