<?php

// tests/Service/StockReservationEngineTest.php
// Unit tests for StockReservationEngine verifying unreserved stock calculations, checkout holds, and confirming orders.
// Connects to: src/Service/StockReservationEngine.php, src/Entity/StockReservation.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\StockReservation;
use App\Repository\StockReservationRepository;
use App\Service\StockManager;
use App\Service\StockReservationEngine;
use PHPUnit\Framework\TestCase;

class StockReservationEngineTest extends TestCase
{
    private StockReservationRepository $reservationRepository;
    private StockManager $stockManager;
    private StockReservationEngine $engine;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(StockReservationRepository::class);
        $this->stockManager = $this->createMock(StockManager::class);

        $this->engine = new StockReservationEngine(
            $this->reservationRepository,
            $this->stockManager
        );
    }

    public function testGetAvailableStockSubtractsHeldReservations(): void
    {
        $product = new Product();
        $product->setStockQuantity(100);

        $this->reservationRepository->method('getReservedQuantitySum')->with($product)->willReturn(30);

        $available = $this->engine->getAvailableStock($product);
        $this->assertEquals(70, $available);
    }

    public function testReserveStockThrowsExceptionWhenInsufficientUnreservedStock(): void
    {
        $product = new Product();
        $product->setSku('OVERSELL-001');
        $product->setStockQuantity(10);

        $this->reservationRepository->method('getReservedQuantitySum')->with($product)->willReturn(8); // Held = 8, Available = 2

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Insufficient unreserved stock/');

        $this->engine->reserveStock($product, 5); // Requesting 5 when only 2 available
    }

    public function testConfirmReservationDeductsPhysicalStock(): void
    {
        $product = new Product();
        $product->setSku('CONFIRM-001');
        $product->setStockQuantity(50);

        $reservation = new StockReservation();
        $reservation->setProduct($product);
        $reservation->setQuantity(5);
        $reservation->setReservationToken('RES-CONFIRM-123');
        $reservation->setStatus(StockReservation::STATUS_RESERVED);

        $this->reservationRepository->method('findOneBy')->with(['reservationToken' => 'RES-CONFIRM-123'])->willReturn($reservation);

        $this->stockManager->expects($this->once())
            ->method('recordMovement')
            ->with($product, StockMovement::TYPE_OUT, 5);

        $confirmed = $this->engine->confirmReservation('RES-CONFIRM-123');

        $this->assertEquals(StockReservation::STATUS_CONFIRMED, $confirmed->getStatus());
    }
}
