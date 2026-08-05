<?php

// src/Controller/PurchaseOrderController.php
// REST API controller managing Purchase Orders and receiving goods shipments.
// Connects to: src/Entity/PurchaseOrder.php, src/Service/PurchaseOrderGenerator.php, src/Repository/PurchaseOrderRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\PurchaseOrderRepository;
use App\Repository\WarehouseRepository;
use App\Service\PurchaseOrderGenerator;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/purchase-orders', name: 'api_v1_po_')]
class PurchaseOrderController extends AbstractController
{
    public function __construct(
        private readonly PurchaseOrderRepository $poRepository,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly PurchaseOrderGenerator $poGenerator,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $criteria = [];
        if ($status) {
            $criteria['status'] = strtoupper($status);
        }

        $orders = $this->poRepository->findBy($criteria, ['createdAt' => 'DESC']);
        $json = $this->serializer->serialize($orders, 'json', ['groups' => ['po:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $order = $this->poRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Purchase Order not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($order, 'json', ['groups' => ['po:read', 'po:detail']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/receive', name: 'receive', methods: ['POST'])]
    public function receive(int $id, Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $order = $this->poRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Purchase Order not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $targetWarehouse = $warehouseId ? $this->warehouseRepository->find($warehouseId) : null;

        try {
            $this->poGenerator->receiveGoods($order, $targetWarehouse);
            $json = $this->serializer->serialize($order, 'json', ['groups' => ['po:read', 'po:detail']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
