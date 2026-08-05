<?php

// src/Controller/WarehouseController.php
// REST API controller managing warehouses, per-location stock levels, and inter-warehouse stock transfers.
// Connects to: src/Entity/Warehouse.php, src/Entity/WarehouseStock.php, src/Service/WarehouseManager.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Warehouse;
use App\Repository\ProductRepository;
use App\Repository\WarehouseRepository;
use App\Repository\WarehouseStockRepository;
use App\Service\WarehouseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/warehouses', name: 'api_v1_warehouses_')]
class WarehouseController extends AbstractController
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
        private readonly WarehouseStockRepository $warehouseStockRepository,
        private readonly ProductRepository $productRepository,
        private readonly WarehouseManager $warehouseManager,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $warehouses = $this->warehouseRepository->findBy([], ['code' => 'ASC']);
        $json = $this->serializer->serialize($warehouses, 'json', ['groups' => ['warehouse:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $warehouse = $this->warehouseRepository->find($id);
        if (!$warehouse) {
            return $this->json(['error' => 'Warehouse not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($warehouse, 'json', ['groups' => ['warehouse:read', 'warehouse:detail']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $code = strtoupper(trim($data['code'] ?? ''));
        if ($code === '') {
            return $this->json(['error' => 'Warehouse code is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = $this->warehouseRepository->findOneBy(['code' => $code]);
        if ($existing) {
            return $this->json(['error' => sprintf('Warehouse with code "%s" already exists', $code)], Response::HTTP_CONFLICT);
        }

        $warehouse = new Warehouse();
        $warehouse->setCode($code);
        $warehouse->setName($data['name'] ?? '');
        $warehouse->setAddress($data['address'] ?? null);

        $errors = $this->validator->validate($warehouse);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->warehouseRepository->save($warehouse, true);
        $json = $this->serializer->serialize($warehouse, 'json', ['groups' => ['warehouse:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}/stock', name: 'adjust_stock', methods: ['POST'])]
    public function adjustStock(int $id, Request $request): JsonResponse
    {
        $warehouse = $this->warehouseRepository->find($id);
        if (!$warehouse) {
            return $this->json(['error' => 'Warehouse not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $productId = isset($data['product_id']) ? (int)$data['product_id'] : null;
        $product = $productId ? $this->productRepository->find($productId) : null;
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $type = $data['type'] ?? null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;

        if (!$type || $quantity === null) {
            return $this->json(['error' => 'Fields "type" and "quantity" are required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $movement = $this->warehouseManager->recordWarehouseMovement(
                $warehouse,
                $product,
                $type,
                $quantity,
                $data['reason'] ?? null,
                $data['reference'] ?? null
            );
            $json = $this->serializer->serialize($movement, 'json', ['groups' => ['movement:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/transfer', name: 'transfer_stock', methods: ['POST'])]
    public function transferStock(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $sourceId = isset($data['source_warehouse_id']) ? (int)$data['source_warehouse_id'] : null;
        $targetId = isset($data['target_warehouse_id']) ? (int)$data['target_warehouse_id'] : null;
        $productId = isset($data['product_id']) ? (int)$data['product_id'] : null;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;

        if (!$sourceId || !$targetId || !$productId || !$quantity) {
            return $this->json(['error' => 'Fields "source_warehouse_id", "target_warehouse_id", "product_id", and "quantity" are required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sourceWarehouse = $this->warehouseRepository->find($sourceId);
        $targetWarehouse = $this->warehouseRepository->find($targetId);
        $product = $this->productRepository->find($productId);

        if (!$sourceWarehouse || !$targetWarehouse || !$product) {
            return $this->json(['error' => 'Source warehouse, target warehouse, or product not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->warehouseManager->transferStock(
                $sourceWarehouse,
                $targetWarehouse,
                $product,
                $quantity,
                $data['reference'] ?? null
            );

            $json = $this->serializer->serialize($result, 'json', ['groups' => ['movement:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
