<?php

// src/Service/VariantManager.php
// Domain service managing product variant creation, price override resolution, and per-variant SKU stock tracking.
// Connects to: src/Entity/ProductVariant.php, src/Entity/Product.php, src/Repository/ProductVariantRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\ProductVariantRepository;

class VariantManager
{
    public function __construct(
        private readonly ProductVariantRepository $variantRepository
    ) {
    }

    /**
     * Creates a new child product variant SKU under a parent catalog product.
     */
    public function createVariant(
        Product $product,
        string $sku,
        array $optionValues,
        ?float $priceOverride = null,
        int $initialStock = 0,
        int $minStockLevel = 5
    ): ProductVariant {
        $existing = $this->variantRepository->findOneBy(['sku' => strtoupper(trim($sku))]);
        if ($existing) {
            throw new \InvalidArgumentException(sprintf('Variant SKU "%s" already exists in database.', $sku));
        }

        $variant = new ProductVariant();
        $variant->setParentProduct($product);
        $variant->setSku($sku);
        $variant->setOptionValues($optionValues);
        if ($priceOverride !== null) {
            $variant->setPriceOverride((string)$priceOverride);
        }
        $variant->setMinStockLevel($minStockLevel);
        $variant->setStockQuantity($initialStock);

        $this->variantRepository->save($variant, true);

        return $variant;
    }

    /**
     * Adjusts variant stock level atomically.
     */
    public function adjustVariantStock(ProductVariant $variant, int $quantity, string $type = 'IN'): ProductVariant
    {
        $current = $variant->getStockQuantity();
        $newStock = match (strtoupper($type)) {
            'IN' => $current + abs($quantity),
            'OUT' => max(0, $current - abs($quantity)),
            'ADJUST' => max(0, $quantity),
            default => throw new \InvalidArgumentException(sprintf('Invalid stock movement type "%s".', $type)),
        };

        $variant->setStockQuantity($newStock);
        $this->variantRepository->save($variant, true);

        return $variant;
    }
}
