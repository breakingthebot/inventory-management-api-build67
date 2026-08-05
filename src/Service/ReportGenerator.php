<?php

// src/Service/ReportGenerator.php
// Domain service generating printable PDF HTML statements and SpreadsheetML Excel XML workbooks.
// Connects to: templates/reports/valuation.html.twig, src/Repository/ProductRepository.php, src/Repository/StockMovementRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use Twig\Environment;

class ReportGenerator
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StockMovementRepository $movementRepository,
        private readonly Environment $twig
    ) {
    }

    /**
     * Generates a print-ready HTML-PDF report for catalog valuation.
     */
    public function generateValuationReportHtml(): string
    {
        $products = $this->productRepository->findAll();
        $items = [];
        $totalUnits = 0;
        $totalValuation = 0.0;

        foreach ($products as $product) {
            $val = (float)$product->getUnitPrice() * $product->getStockQuantity();
            $items[] = [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'category' => $product->getCategory() ? $product->getCategory()->getName() : 'Uncategorized',
                'unit_price' => (float)$product->getUnitPrice(),
                'stock_quantity' => $product->getStockQuantity(),
                'total_value' => $val,
            ];

            $totalUnits += $product->getStockQuantity();
            $totalValuation += $val;
        }

        return $this->twig->render('reports/valuation.html.twig', [
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s T'),
            'total_skus' => count($items),
            'total_units' => $totalUnits,
            'total_valuation' => $totalValuation,
            'items' => $items,
        ]);
    }

    /**
     * Generates a SpreadsheetML XML document for Excel export of stock audit logs.
     */
    public function generateStockMovementsExcelXml(): string
    {
        $movements = $this->movementRepository->findBy([], ['createdAt' => 'DESC'], 500);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1E293B" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= ' </Styles>' . "\n";
        $xml .= ' <Worksheet ss:Name="Stock Movement Audit Log">' . "\n";
        $xml .= '  <Table>' . "\n";
        $xml .= '   <Row ss:StyleID="Header">' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Movement ID</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Date &amp; Time</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">SKU</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Product Name</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Type</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Quantity Delta</Data></Cell>' . "\n";
        $xml .= '    <Cell><Data ss:Type="String">Reason / Reference</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        foreach ($movements as $m) {
            $xml .= '   <Row>' . "\n";
            $xml .= sprintf('    <Cell><Data ss:Type="Number">%d</Data></Cell>' . "\n", $m->getId());
            $xml .= sprintf('    <Cell><Data ss:Type="String">%s</Data></Cell>' . "\n", htmlspecialchars($m->getCreatedAt()->format('Y-m-d H:i:s')));
            $xml .= sprintf('    <Cell><Data ss:Type="String">%s</Data></Cell>' . "\n", htmlspecialchars($m->getProduct() ? $m->getProduct()->getSku() : ''));
            $xml .= sprintf('    <Cell><Data ss:Type="String">%s</Data></Cell>' . "\n", htmlspecialchars($m->getProduct() ? $m->getProduct()->getName() : ''));
            $xml .= sprintf('    <Cell><Data ss:Type="String">%s</Data></Cell>' . "\n", htmlspecialchars($m->getType()));
            $xml .= sprintf('    <Cell><Data ss:Type="Number">%d</Data></Cell>' . "\n", $m->getQuantity());
            $xml .= sprintf('    <Cell><Data ss:Type="String">%s</Data></Cell>' . "\n", htmlspecialchars($m->getReason() ?? ''));
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return $xml;
    }
}
