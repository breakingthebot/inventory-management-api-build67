<?php

// tests/Service/VariantManagerTest.php
// Unit tests for VariantManager verifying variant SKU creation, price override resolution, and per-variant stock tracking.
// Connects to: src/Service/VariantManager.php, src/Entity/ProductVariant.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\ProductVariantRepository;
use App\Service\VariantManager;
use PHPUnit\Framework\TestCase;

class VariantManagerTest extends TestCase
{
    private ProductVariantRepository $variantRepository;
    private VariantManager $manager;

    protected function setUp(): void
    {
        $this->variantRepository = $this->createMock(ProductVariantRepository::class);
        $this->manager = new VariantManager($this->variantRepository);
    }

    public function testCreateVariantSetsPropertiesAndPriceOverride(): void
    {
        $product = new Product();
        $product->setSku('SHIRT-BASE');
        $product->setUnitPrice(25.0);

        $this->variantRepository->method('findOneBy')->with(['sku' => 'SHIRT-RED-XL'])->willReturn(null);
        $this->variantRepository->expects($this->once())->method('save');

        $variant = $this->manager->createVariant(
            $product,
            'SHIRT-RED-XL',
            ['color' => 'Red', 'size' => 'XL'],
            29.99,
            15,
            5
        );

        $this->assertEquals('SHIRT-RED-XL', $variant->getSku());
        $this->assertEquals(29.99, $variant->getEffectivePrice());
        $this->assertEquals(15, $variant->getStockQuantity());
        $this->assertEquals('IN_STOCK', $variant->getStatus());
        $this->assertEquals(['color' => 'Red', 'size' => 'XL'], $variant->getOptionValues());
    }

    public function testEffectivePriceFallbackToParentPriceWhenNoOverride(): void
    {
        $product = new Product();
        $product->setUnitPrice(45.0);

        $variant = new ProductVariant();
        $variant->setParentProduct($product);

        $this->assertEquals(45.0, $variant->getEffectivePrice());
    }

    public function testAdjustVariantStockRecalculatesStatus(): void
    {
        $variant = new ProductVariant();
        $variant->setStockQuantity(10);
        $variant->setMinStockLevel(5);

        $this->variantRepository->expects($this->once())->method('save')->with($variant);

        $this->manager->adjustVariantStock($variant, 8, 'OUT');

        $this->assertEquals(2, $variant->getStockQuantity());
        $this->assertEquals('LOW_STOCK', $variant->getStatus());
    }
}
