<?php

// tests/Service/CsvBatchImporterTest.php
// Unit & Integration tests for CsvBatchImporter including a Chaos Fixture exercising multi-rule batch error collection.
// Connects to: src/Service/CsvBatchImporter.php, src/Entity/Product.php, src/Entity/Category.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\CsvBatchImporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvBatchImporterTest extends TestCase
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private CsvBatchImporter $importer;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->importer = new CsvBatchImporter(
            $this->productRepository,
            $this->categoryRepository,
            $this->entityManager,
            $this->validator
        );
    }

    public function testValidCsvImport(): void
    {
        $csv = "sku,name,description,unit_price,stock_quantity,min_stock_level,category\n" .
               "CSV-001,Gaming Mouse,Ergonomic RGB Mouse,49.99,25,5,Peripherals\n" .
               "CSV-002,Mechanical Keyboard,Clicky Blue Switches,89.99,15,3,Peripherals\n";

        $this->productRepository->method('findOneBy')->willReturn(null);
        $this->categoryRepository->method('findOneBy')->willReturn(null);

        $this->categoryRepository->expects($this->exactly(2))->method('save');
        $this->productRepository->expects($this->exactly(2))->method('save');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->importer->importCsv($csv);

        $this->assertEquals(2, $result['total_rows']);
        $this->assertEquals(2, $result['successful_rows']);
        $this->assertEquals(0, $result['failed_rows']);
        $this->assertEquals(2, $result['created_count']);
        $this->assertEmpty($result['errors']);
    }

    public function testChaosFixtureBatchValidationErrorCollection(): void
    {
        // Chaos CSV fixture containing:
        // Row 2: Valid item
        // Row 3: Blank SKU
        // Row 4: Column count mismatch
        // Row 5: Valid item
        $chaosCsv = "sku,name,description,unit_price,stock_quantity,min_stock_level,category\n" .
                    "GOOD-1,Valid Item 1,Proper item,19.99,10,2,Gadgets\n" .
                    ",Missing SKU Item,No sku provided,10.00,5,1,Gadgets\n" .
                    "BAD-COLS,Extra Column Item,This line has too many values,15.00,5,1,Gadgets,EXTRA_VAL\n" .
                    "GOOD-2,Valid Item 2,Another good item,29.99,8,2,Gadgets\n";

        $this->productRepository->method('findOneBy')->willReturn(null);

        $result = $this->importer->importCsv($chaosCsv);

        $this->assertEquals(3, $result['total_rows']); // 3 rows processed with SKU checks
        $this->assertEquals(2, $result['successful_rows']);
        $this->assertEquals(2, $result['failed_rows']); // Row 3 & Row 4 failed
        $this->assertCount(2, $result['errors']);
        $this->assertEquals(3, $result['errors'][0]['row']);
        $this->assertEquals(4, $result['errors'][1]['row']);
    }
}
