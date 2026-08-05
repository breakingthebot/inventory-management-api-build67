<?php

// src/Controller/StockMovementController.php
// REST API controller managing stock movements (restock IN, sales OUT, inventory ADJUST) and audit trails.
// Connects to: src/Entity/Product.php, src/Entity/StockMovement.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use App\Service\StockManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/products', name: 'api_v1_stock_')]
class StockMovementController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StockMovementRepository $stockMovementRepository,
        private readonly StockManager $stockManager,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/{id}/stock', name: 'adjust_stock', methods: ['POST'])]
    public function adjustStock(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $type = $data['type'] ?? null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
        $reason = $data['reason'] ?? null;
        $reference = $data['reference'] ?? null;

        if (!$type || $quantity === null) {
            return $this->json(['error' => 'Fields "type" and "quantity" are required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $movement = $this->stockManager->recordMovement($product, $type, $quantity, $reason, $reference);
            $json = $this->serializer->serialize($movement, 'json', ['groups' => ['movement:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/stock-movements', name: 'list_movements', methods: ['GET'])]
    public function listMovements(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $movements = $this->stockMovementRepository->findBy(['product' => $product], ['createdAt' => 'DESC']);
        $json = $this->serializer->serialize($movements, 'json', ['groups' => ['movement:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
