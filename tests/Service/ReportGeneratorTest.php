<?php

// tests/Service/ReportGeneratorTest.php
// Unit tests for ReportGenerator service verifying HTML printable statements and Excel XML generation.
// Connects to: src/Service/ReportGenerator.php, templates/reports/valuation.html.twig
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\StockMovement;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use App\Service\ReportGenerator;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ReportGeneratorTest extends TestCase
{
    private ProductRepository $productRepository;
    private StockMovementRepository $movementRepository;
    private Environment $twig;
    private ReportGenerator $generator;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->movementRepository = $this->createMock(StockMovementRepository::class);
        $this->twig = $this->createMock(Environment::class);

        $this->generator = new ReportGenerator(
            $this->productRepository,
            $this->movementRepository,
            $this->twig
        );
    }

    public function testGenerateValuationReportHtmlRendersTemplateWithCalculatedTotals(): void
    {
        $cat = new Category();
        $cat->setName('Electronics');

        $p1 = new Product();
        $p1->setSku('REP-001');
        $p1->setName('Laptop');
        $p1->setUnitPrice(1000.0);
        $p1->setStockQuantity(5);
        $p1->setCategory($cat);

        $this->productRepository->method('findAll')->willReturn([$p1]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'reports/valuation.html.twig',
                $this->callback(function ($context) {
                    return $context['total_skus'] === 1
                        && $context['total_units'] === 5
                        && $context['total_valuation'] === 5000.0;
                })
            )
            ->willReturn('<html>Valuation Report</html>');

        $html = $this->generator->generateValuationReportHtml();
        $this->assertEquals('<html>Valuation Report</html>', $html);
    }

    public function testGenerateStockMovementsExcelXmlReturnsValidSpreadsheetML(): void
    {
        $m1 = new StockMovement();
        $m1->setType(StockMovement::TYPE_IN);
        $m1->setQuantity(100);
        $m1->setReason('Initial restock');

        $this->movementRepository->method('findBy')->willReturn([$m1]);

        $xml = $this->generator->generateStockMovementsExcelXml();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"', $xml);
        $this->assertStringContainsString('<Worksheet ss:Name="Stock Movement Audit Log">', $xml);
        $this->assertStringContainsString('<Data ss:Type="String">Initial restock</Data>', $xml);
    }
}
