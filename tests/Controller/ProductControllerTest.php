<?php

// tests/Controller/ProductControllerTest.php
// Unit tests for Product entity methods, status recalculations, and validation constraints.
// Connects to: src/Entity/Product.php, src/Entity/Category.php
// Created: 2026-08-05

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductControllerTest extends TestCase
{
    public function testProductInitializationAndDefaults(): void
    {
        $product = new Product();
        $product->setSku('SKU-100');
        $product->setName('Wireless Mouse');
        $product->setUnitPrice(29.99);

        $this->assertEquals('SKU-100', $product->getSku());
        $this->assertEquals('Wireless Mouse', $product->getName());
        $this->assertEquals('29.99', $product->getUnitPrice());
        $this->assertEquals(0, $product->getStockQuantity());
        $this->assertEquals(Product::STATUS_OUT_OF_STOCK, $product->getStatus());
    }

    public function testProductStatusTransitions(): void
    {
        $product = new Product();
        $product->setMinStockLevel(5);

        // Case 1: Out of stock (0)
        $product->setStockQuantity(0);
        $this->assertEquals(Product::STATUS_OUT_OF_STOCK, $product->getStatus());

        // Case 2: Low stock (3 <= 5)
        $product->setStockQuantity(3);
        $this->assertEquals(Product::STATUS_LOW_STOCK, $product->getStatus());

        // Case 3: In stock (15 > 5)
        $product->setStockQuantity(15);
        $this->assertEquals(Product::STATUS_IN_STOCK, $product->getStatus());
    }

    public function testCategoryLinkage(): void
    {
        $category = new Category();
        $category->setName('Electronics');

        $product = new Product();
        $product->setName('Headphones');
        $product->setCategory($category);

        $this->assertSame($category, $product->getCategory());
        $this->assertEquals('electronics', $category->getSlug());
    }
}
