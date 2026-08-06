<?php

// src/Controller/BackorderController.php
// REST API controller managing customer backorder placements and FIFO queue inspections.
// Connects to: src/Entity/Backorder.php, src/Service/BackorderManager.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Backorder;
use App\Repository\BackorderRepository;
use App\Repository\ProductRepository;
use App\Service\BackorderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/backorders', name: 'api_v1_backorders_')]
class BackorderController extends AbstractController
{
    public function __construct(
        private readonly BackorderRepository $backorderRepository,
        private readonly ProductRepository $productRepository,
        private readonly BackorderManager $backorderManager,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $productId = $request->query->get('product_id');

        $criteria = [];
        if ($status) {
            $criteria['status'] = strtoupper($status);
        }
        if ($productId) {
            $criteria['product'] = (int)$productId;
        }

        $backorders = $this->backorderRepository->findBy($criteria, ['createdAt' => 'ASC']);
        $json = $this->serializer->serialize($backorders, 'json', ['groups' => ['backorder:read', 'product:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['product_id'], $data['customer_email'])) {
            return $this->json(['error' => 'Required fields: product_id, customer_email'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find((int)$data['product_id']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

        try {
            $backorder = $this->backorderManager->createBackorder($product, $data['customer_email'], $quantity);
            $json = $this->serializer->serialize($backorder, 'json', ['groups' => ['backorder:read', 'product:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(int $id): JsonResponse
    {
        $backorder = $this->backorderRepository->find($id);
        if (!$backorder) {
            return $this->json(['error' => 'Backorder not found'], Response::HTTP_NOT_FOUND);
        }

        if ($backorder->getStatus() !== Backorder::STATUS_PENDING) {
            return $this->json(['error' => sprintf('Cannot cancel backorder in status "%s"', $backorder->getStatus())], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $backorder->setStatus(Backorder::STATUS_CANCELLED);
        $this->backorderRepository->save($backorder, true);

        $json = $this->serializer->serialize($backorder, 'json', ['groups' => ['backorder:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
