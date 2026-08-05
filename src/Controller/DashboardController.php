<?php

// src/Controller/DashboardController.php
// Controller rendering the interactive web dashboard with metrics, stock breakdown, and FEFO alerts.
// Connects to: src/Repository/ProductRepository.php, src/Repository/WarehouseRepository.php, src/Repository/PurchaseOrderRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Repository\BatchLotRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\StockMovementRepository;
use App\Repository\WarehouseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly PurchaseOrderRepository $poRepository,
        private readonly StockMovementRepository $movementRepository,
        private readonly BatchLotRepository $batchLotRepository
    ) {
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $allProducts = $this->productRepository->findAll();
        $totalProducts = count($allProducts);

        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;
        $totalValuation = 0.0;

        foreach ($allProducts as $p) {
            $status = $p->getStatus();
            if ($status === Product::STATUS_IN_STOCK) {
                $inStock++;
            } elseif ($status === Product::STATUS_LOW_STOCK) {
                $lowStock++;
            } else {
                $outOfStock++;
            }
            $totalValuation += ($p->getStockQuantity() * (float)$p->getUnitPrice());
        }

        $activeWarehouses = $this->warehouseRepository->count([]);
        $pendingPOs = $this->poRepository->count(['status' => [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SUBMITTED]]);
        $recentMovements = $this->movementRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $expiringLots = $this->batchLotRepository->findExpiringLots(30);

        return $this->render('dashboard/index.html.twig', [
            'total_products' => $totalProducts,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_valuation' => $totalValuation,
            'active_warehouses' => $activeWarehouses,
            'pending_pos' => $pendingPOs,
            'recent_movements' => $recentMovements,
            'expiring_lots' => $expiringLots,
        ]);
    }
}
