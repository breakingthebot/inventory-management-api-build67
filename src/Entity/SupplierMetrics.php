<?php

// src/Entity/SupplierMetrics.php
// Doctrine Entity representing aggregated vendor performance analytics scorecards.
// Connects to: src/Entity/Supplier.php, src/Repository/SupplierMetricsRepository.php
// Created: 2026-08-05

namespace App\Entity;

use App\Repository\SupplierMetricsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SupplierMetricsRepository::class)]
#[ORM\Table(name: 'supplier_metrics')]
class SupplierMetrics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['supplier_metrics:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['supplier_metrics:read'])]
    private ?Supplier $supplier = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['supplier_metrics:read'])]
    private int $totalOrdersPlaced = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['supplier_metrics:read'])]
    private int $totalOrdersReceived = 0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['supplier_metrics:read'])]
    private float $averageLeadTimeDays = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['supplier_metrics:read'])]
    private float $fulfillmentAccuracyPercentage = 100.0;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['supplier_metrics:read'])]
    private float $defectiveRatePercentage = 0.0;

    #[ORM\Column]
    #[Groups(['supplier_metrics:read'])]
    private \DateTimeImmutable $lastEvaluatedAt;

    public function __construct()
    {
        $this->lastEvaluatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getTotalOrdersPlaced(): int
    {
        return $this->totalOrdersPlaced;
    }

    public function setTotalOrdersPlaced(int $totalOrdersPlaced): self
    {
        $this->totalOrdersPlaced = max(0, $totalOrdersPlaced);
        return $this;
    }

    public function getTotalOrdersReceived(): int
    {
        return $this->totalOrdersReceived;
    }

    public function setTotalOrdersReceived(int $totalOrdersReceived): self
    {
        $this->totalOrdersReceived = max(0, $totalOrdersReceived);
        return $this;
    }

    public function getAverageLeadTimeDays(): float
    {
        return $this->averageLeadTimeDays;
    }

    public function setAverageLeadTimeDays(float $averageLeadTimeDays): self
    {
        $this->averageLeadTimeDays = max(0.0, round($averageLeadTimeDays, 2));
        return $this;
    }

    public function getFulfillmentAccuracyPercentage(): float
    {
        return $this->fulfillmentAccuracyPercentage;
    }

    public function setFulfillmentAccuracyPercentage(float $fulfillmentAccuracyPercentage): self
    {
        $this->fulfillmentAccuracyPercentage = max(0.0, min(100.0, round($fulfillmentAccuracyPercentage, 2)));
        return $this;
    }

    public function getDefectiveRatePercentage(): float
    {
        return $this->defectiveRatePercentage;
    }

    public function setDefectiveRatePercentage(float $defectiveRatePercentage): self
    {
        $this->defectiveRatePercentage = max(0.0, min(100.0, round($defectiveRatePercentage, 2)));
        return $this;
    }

    public function getLastEvaluatedAt(): \DateTimeImmutable
    {
        return $this->lastEvaluatedAt;
    }

    public function setLastEvaluatedAt(\DateTimeImmutable $lastEvaluatedAt): self
    {
        $this->lastEvaluatedAt = $lastEvaluatedAt;
        return $this;
    }
}
