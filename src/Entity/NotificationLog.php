<?php

// src/Entity/NotificationLog.php
// Doctrine Entity logging outbound alert notifications (Email & Webhook dispatches).
// Connects to: src/Repository/NotificationLogRepository.php, src/Service/NotificationService.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\NotificationLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationLogRepository::class)]
#[ORM\Table(name: 'notification_logs')]
class NotificationLog
{
    public const TYPE_EMAIL = 'EMAIL';
    public const TYPE_WEBHOOK = 'WEBHOOK';

    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['log:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['log:read'])]
    private ?string $channel = null;

    #[ORM\Column(length: 255)]
    #[Groups(['log:read'])]
    private ?string $recipient = null;

    #[ORM\Column(length: 100)]
    #[Groups(['log:read'])]
    private ?string $event = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['log:read'])]
    private array $payload = [];

    #[ORM\Column(length: 20)]
    #[Groups(['log:read'])]
    private string $status = self::STATUS_SENT;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['log:read'])]
    private ?int $responseCode = null;

    #[ORM\Column]
    #[Groups(['log:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = strtoupper($channel);
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        return $this;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(?int $responseCode): self
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
