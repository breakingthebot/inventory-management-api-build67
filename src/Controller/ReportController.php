<?php

// src/Controller/ReportController.php
// REST API controller generating and streaming PDF and Excel XML reports.
// Connects to: src/Service/ReportGenerator.php
// Created: 2026-08-05

namespace App\Controller;

use App\Service\ReportGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/reports', name: 'api_v1_reports_')]
class ReportController extends AbstractController
{
    public function __construct(
        private readonly ReportGenerator $reportGenerator
    ) {
    }

    #[Route('/valuation/pdf', name: 'valuation_pdf', methods: ['GET'])]
    public function valuationPdf(): Response
    {
        $html = $this->reportGenerator->generateValuationReportHtml();

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="inventory-valuation-report.html"',
        ]);
    }

    #[Route('/stock-movements/excel', name: 'movements_excel', methods: ['GET'])]
    public function movementsExcel(): Response
    {
        $xml = $this->reportGenerator->generateStockMovementsExcelXml();

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stock-movements-audit.xml"',
        ]);
    }
}
