<?php

// tests/Service/SupplierAnalyticsEngineTest.php
// Unit tests for SupplierAnalyticsEngine verifying vendor lead times and fulfillment accuracy scorecards.
// Connects to: src/Service/SupplierAnalyticsEngine.php, src/Entity/SupplierMetrics.php, src/Entity/PurchaseOrder.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Entity\SupplierMetrics;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SupplierMetricsRepository;
use App\Service\SupplierAnalyticsEngine;
use PHPUnit\Framework\TestCase;

class SupplierAnalyticsEngineTest extends TestCase
{
    private PurchaseOrderRepository $poRepository;
    private SupplierMetricsRepository $metricsRepository;
    private SupplierAnalyticsEngine $engine;

    protected function setUp(): void
    {
        $this->poRepository = $this->createMock(PurchaseOrderRepository::class);
        $this->metricsRepository = $this->createMock(SupplierMetricsRepository::class);

        $this->engine = new SupplierAnalyticsEngine(
            $this->poRepository,
            $this->metricsRepository
        );
    }

    public function testCalculateSupplierScorecardComputesAccuracyAndLeadTime(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Tech Component Supply');
        $supplier->setLeadTimeDays(7);

        $po1 = new PurchaseOrder();
        $po1->setSupplier($supplier);
        $po1->setStatus(PurchaseOrder::STATUS_RECEIVED);

        $created = new \DateTimeImmutable('-10 days');
        $received = new \DateTimeImmutable('-6 days'); // 4 days lead time

        $refCreated = new \ReflectionProperty(PurchaseOrder::class, 'createdAt');
        $refCreated->setValue($po1, $created);
        $po1->setReceivedAt($received);

        $po2 = new PurchaseOrder();
        $po2->setSupplier($supplier);
        $po2->setStatus(PurchaseOrder::STATUS_CANCELLED); // 1 cancelled out of 2 total = 50% accuracy

        $this->poRepository->method('findBy')->with(['supplier' => $supplier])->willReturn([$po1, $po2]);
        $this->metricsRepository->method('findOneBy')->with(['supplier' => $supplier])->willReturn(null);
        $this->metricsRepository->expects($this->once())->method('save');

        $metrics = $this->engine->calculateSupplierScorecard($supplier);

        $this->assertEquals(2, $metrics->getTotalOrdersPlaced());
        $this->assertEquals(1, $metrics->getTotalOrdersReceived());
        $this->assertEquals(50.0, $metrics->getFulfillmentAccuracyPercentage());
        $this->assertEquals(4.0, $metrics->getAverageLeadTimeDays());
    }
}
