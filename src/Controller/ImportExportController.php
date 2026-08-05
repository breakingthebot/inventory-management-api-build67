<?php

// src/Controller/ImportExportController.php
// REST API controller handling CSV bulk import and streamed CSV exports.
// Connects to: src/Service/CsvBatchImporter.php, src/Service/CsvExporter.php
// Created: 2026-08-05

namespace App\Controller;

use App\Service\CsvBatchImporter;
use App\Service\CsvExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1', name: 'api_v1_import_export_')]
class ImportExportController extends AbstractController
{
    public function __construct(
        private readonly CsvBatchImporter $csvBatchImporter,
        private readonly CsvExporter $csvExporter
    ) {
    }

    #[Route('/products/import/csv', name: 'import_products_csv', methods: ['POST'])]
    public function importProductsCsv(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $csvContent = '';

        if ($file) {
            $csvContent = file_get_contents($file->getPathname());
        } else {
            $csvContent = $request->getContent();
        }

        if (trim($csvContent) === '') {
            return $this->json(['error' => 'No CSV content or file provided.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->csvBatchImporter->importCsv($csvContent);
            return $this->json($result, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/products/export/csv', name: 'export_products_csv', methods: ['GET'])]
    public function exportProductsCsv(): Response
    {
        $csvData = $this->csvExporter->exportProductsCsv();

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="products_export_' . date('Ymd_His') . '.csv"');

        return $response;
    }

    #[Route('/stock-movements/export/csv', name: 'export_movements_csv', methods: ['GET'])]
    public function exportMovementsCsv(): Response
    {
        $csvData = $this->csvExporter->exportStockMovementsCsv();

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="stock_movements_' . date('Ymd_His') . '.csv"');

        return $response;
    }
}
