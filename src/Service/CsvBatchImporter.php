<?php

// src/Service/CsvBatchImporter.php
// Batch CSV import service parsing product inventory spreadsheets with per-row validation and error aggregation.
// Connects to: src/Entity/Product.php, src/Entity/Category.php, src/Repository/ProductRepository.php, src/Repository/CategoryRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvBatchImporter
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Imports products from a CSV content string or stream.
     *
     * Expected CSV headers: sku, name, description, unit_price, stock_quantity, min_stock_level, category
     *
     * @return array{
     *     total_rows: int,
     *     successful_rows: int,
     *     failed_rows: int,
     *     created_count: int,
     *     updated_count: int,
     *     errors: array<int, array{row: int, sku: string, errors: string[]}>
     * }
     */
    public function importCsv(string $csvContent): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", trim($csvContent)));
        if (count($lines) < 2) {
            throw new \InvalidArgumentException('CSV content must contain a header row and at least one data row.');
        }

        $headerLine = array_shift($lines);
        $headers = array_map(fn($h) => strtolower(trim($h)), str_getcsv($headerLine));

        $requiredHeaders = ['sku', 'name'];
        foreach ($requiredHeaders as $req) {
            if (!in_array($req, $headers, true)) {
                throw new \InvalidArgumentException(sprintf('Missing required CSV header: "%s".', $req));
            }
        }

        $totalRows = 0;
        $successfulRows = 0;
        $failedRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $rowNumber = $index + 2; // Accounting for 1-based index and header row
            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line);
            if (count($row) !== count($headers)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => 'N/A',
                    'errors' => [sprintf('Column count mismatch. Expected %d columns, got %d.', count($headers), count($row))],
                ];
                $failedRows++;
                continue;
            }

            $totalRows++;
            $data = array_combine($headers, array_map('trim', $row));
            $sku = strtoupper($data['sku'] ?? '');

            if ($sku === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => 'N/A',
                    'errors' => ['SKU is required and cannot be blank.'],
                ];
                $failedRows++;
                continue;
            }

            $product = $this->productRepository->findOneBy(['sku' => $sku]);
            $isNew = false;

            if (!$product) {
                $product = new Product();
                $product->setSku($sku);
                $isNew = true;
            }

            if (isset($data['name']) && $data['name'] !== '') {
                $product->setName($data['name']);
            }
            if (isset($data['description'])) {
                $product->setDescription($data['description']);
            }
            if (isset($data['unit_price']) && $data['unit_price'] !== '') {
                $product->setUnitPrice($data['unit_price']);
            }
            if (isset($data['stock_quantity']) && $data['stock_quantity'] !== '') {
                $product->setStockQuantity((int)$data['stock_quantity']);
            }
            if (isset($data['min_stock_level']) && $data['min_stock_level'] !== '') {
                $product->setMinStockLevel((int)$data['min_stock_level']);
            }

            if (isset($data['category']) && $data['category'] !== '') {
                $categoryName = $data['category'];
                $category = $this->categoryRepository->findOneBy(['name' => $categoryName]);
                if (!$category) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $this->categoryRepository->save($category, true);
                }
                $product->setCategory($category);
            }

            $validationErrors = $this->validator->validate($product);
            if (count($validationErrors) > 0) {
                $messages = [];
                foreach ($validationErrors as $error) {
                    $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
                }
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'errors' => $messages,
                ];
                $failedRows++;
                continue;
            }

            $this->productRepository->save($product, false);
            $successfulRows++;
            if ($isNew) {
                $createdCount++;
            } else {
                $updatedCount++;
            }
        }

        $this->entityManager->flush();

        return [
            'total_rows' => $totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ];
    }
}
