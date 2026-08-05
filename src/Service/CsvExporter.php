<?php

// src/Service/CsvExporter.php
// Streaming CSV export service for products catalog and stock movement audit logs.
// Connects to: src/Entity/Product.php, src/Entity/StockMovement.php, src/Repository/ProductRepository.php, src/Repository/StockMovementRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;

class CsvExporter
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StockMovementRepository $stockMovementRepository
    ) {
    }

    public function exportProductsCsv(): string
    {
        $products = $this->productRepository->findBy([], ['createdAt' => 'DESC']);
        $fp = fopen('php://temp', 'r+');

        fputcsv($fp, ['id', 'sku', 'name', 'category', 'unit_price', 'stock_quantity', 'min_stock_level', 'status', 'created_at']);

        foreach ($products as $product) {
            fputcsv($fp, [
                $product->getId(),
                $product->getSku(),
                $product->getName(),
                $product->getCategory()?->getName() ?? '',
                $product->getUnitPrice(),
                $product->getStockQuantity(),
                $product->getMinStockLevel(),
                $product->getStatus(),
                $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    public function exportStockMovementsCsv(): string
    {
        $movements = $this->stockMovementRepository->findBy([], ['createdAt' => 'DESC']);
        $fp = fopen('php://temp', 'r+');

        fputcsv($fp, ['id', 'sku', 'product_name', 'warehouse_code', 'type', 'quantity', 'resulting_stock', 'reason', 'reference', 'created_at']);

        foreach ($movements as $m) {
            fputcsv($fp, [
                $m->getId(),
                $m->getProduct()?->getSku() ?? '',
                $m->getProduct()?->getName() ?? '',
                $m->getWarehouse()?->getCode() ?? 'GLOBAL',
                $m->getType(),
                $m->getQuantity(),
                $m->getResultingStock(),
                $m->getReason() ?? '',
                $m->getReference() ?? '',
                $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}
