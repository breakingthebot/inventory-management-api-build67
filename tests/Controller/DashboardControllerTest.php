<?php

// tests/Controller/DashboardControllerTest.php
// Integration & Unit tests verifying DashboardController metric calculation and view rendering.
// Connects to: src/Controller/DashboardController.php
// Created: 2026-08-05

namespace App\Tests\Controller;

use App\Controller\DashboardController;
use App\Entity\Product;
use App\Repository\BatchLotRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\StockMovementRepository;
use App\Repository\WarehouseRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DashboardControllerTest extends TestCase
{
    public function testDashboardControllerCalculatesMetrics(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $warehouseRepository = $this->createMock(WarehouseRepository::class);
        $poRepository = $this->createMock(PurchaseOrderRepository::class);
        $movementRepository = $this->createMock(StockMovementRepository::class);
        $batchLotRepository = $this->createMock(BatchLotRepository::class);
        $twig = $this->createMock(Environment::class);

        $product = new Product();
        $product->setSku('PROD-DASH-1');
        $product->setUnitPrice(100.0);
        $product->setStockQuantity(10);
        $product->setStatus(Product::STATUS_IN_STOCK);

        $productRepository->method('findAll')->willReturn([$product]);
        $warehouseRepository->method('count')->willReturn(2);
        $poRepository->method('count')->willReturn(1);
        $movementRepository->method('findBy')->willReturn([]);
        $batchLotRepository->method('findExpiringLots')->willReturn([]);

        $twig->expects($this->once())
            ->method('render')
            ->with('dashboard/index.html.twig', $this->callback(function (array $params) {
                return $params['total_products'] === 1
                    && $params['in_stock'] === 1
                    && $params['total_valuation'] === 1000.0
                    && $params['active_warehouses'] === 2;
            }))
            ->willReturn('<html>Dashboard Rendered</html>');

        $controller = new DashboardController(
            $productRepository,
            $warehouseRepository,
            $poRepository,
            $movementRepository,
            $batchLotRepository
        );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('twig')->willReturn(true);
        $container->method('get')->with('twig')->willReturn($twig);

        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('<html>Dashboard Rendered</html>', $response->getContent());
    }
}
