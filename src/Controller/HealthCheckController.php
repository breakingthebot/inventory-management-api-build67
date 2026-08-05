<?php

// src/Controller/HealthCheckController.php
// REST API health check endpoint providing service diagnostic status.
// Connects to: Symfony AbstractController
// Created: 2026-08-05

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1', name: 'api_v1_')]
class HealthCheckController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'service' => 'Inventory Management API',
            'framework' => 'Symfony 6.4',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_OK);
    }
}
