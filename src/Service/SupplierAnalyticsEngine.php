<?php

// src/Service/SupplierAnalyticsEngine.php
// Domain service calculating vendor lead times, fulfillment accuracy percentages, and scorecard metrics.
// Connects to: src/Entity/Supplier.php, src/Entity/SupplierMetrics.php, src/Entity/PurchaseOrder.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Entity\SupplierMetrics;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SupplierMetricsRepository;

class SupplierAnalyticsEngine
{
    public function __construct(
        private readonly PurchaseOrderRepository $poRepository,
        private readonly SupplierMetricsRepository $metricsRepository
    ) {
    }

    /**
     * Calculates vendor lead time latency and order fulfillment accuracy metrics.
     */
    public function calculateSupplierScorecard(Supplier $supplier): SupplierMetrics
    {
        $orders = $this->poRepository->findBy(['supplier' => $supplier]);

        $metrics = $this->metricsRepository->findOneBy(['supplier' => $supplier]) ?? new SupplierMetrics();
        $metrics->setSupplier($supplier);

        $totalPlaced = count($orders);
        $metrics->setTotalOrdersPlaced($totalPlaced);

        $receivedOrders = array_filter($orders, fn(PurchaseOrder $po) => $po->getStatus() === PurchaseOrder::STATUS_RECEIVED);
        $totalReceived = count($receivedOrders);
        $metrics->setTotalOrdersReceived($totalReceived);

        if ($totalReceived > 0) {
            $totalLeadDays = 0.0;
            foreach ($receivedOrders as $po) {
                $receivedAt = $po->getReceivedAt() ?? new \DateTimeImmutable();
                $diffSeconds = $receivedAt->getTimestamp() - $po->getCreatedAt()->getTimestamp();
                $totalLeadDays += max(0, $diffSeconds / 86400);
            }
            $metrics->setAverageLeadTimeDays($totalLeadDays / $totalReceived);
        } else {
            $metrics->setAverageLeadTimeDays((float)$supplier->getLeadTimeDays());
        }

        $accuracy = $totalPlaced > 0 ? ($totalReceived / $totalPlaced) * 100.0 : 100.0;
        $metrics->setFulfillmentAccuracyPercentage($accuracy);
        $metrics->setLastEvaluatedAt(new \DateTimeImmutable());

        $this->metricsRepository->save($metrics, true);

        return $metrics;
    }
}
