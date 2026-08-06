<?php

// src/Controller/SupplierAnalyticsController.php
// REST API controller for inspecting supplier scorecards, lead times, and fulfillment accuracy leaderboards.
// Connects to: src/Entity/SupplierMetrics.php, src/Service/SupplierAnalyticsEngine.php, src/Repository/SupplierRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Repository\SupplierMetricsRepository;
use App\Repository\SupplierRepository;
use App\Service\SupplierAnalyticsEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SupplierAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository,
        private readonly SupplierMetricsRepository $metricsRepository,
        private readonly SupplierAnalyticsEngine $analyticsEngine,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/api/v1/suppliers/analytics/leaderboard', name: 'api_v1_supplier_analytics_leaderboard', methods: ['GET'])]
    public function leaderboard(): JsonResponse
    {
        $top = $this->metricsRepository->findTopPerformers(10);
        $json = $this->serializer->serialize($top, 'json', ['groups' => ['supplier_metrics:read', 'supplier:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/suppliers/{id}/metrics', name: 'api_v1_supplier_metrics_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierRepository->find($id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $metrics = $this->metricsRepository->findOneBy(['supplier' => $supplier]);
        if (!$metrics) {
            $metrics = $this->analyticsEngine->calculateSupplierScorecard($supplier);
        }

        $json = $this->serializer->serialize($metrics, 'json', ['groups' => ['supplier_metrics:read', 'supplier:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/suppliers/{id}/metrics/recalculate', name: 'api_v1_supplier_metrics_recalculate', methods: ['POST'])]
    public function recalculate(int $id): JsonResponse
    {
        $supplier = $this->supplierRepository->find($id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $metrics = $this->analyticsEngine->calculateSupplierScorecard($supplier);
        $json = $this->serializer->serialize($metrics, 'json', ['groups' => ['supplier_metrics:read', 'supplier:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
