<?php

// src/Controller/BatchLotController.php
// REST API controller for batch lot tracking, FEFO inventory allocation, and expiration reports.
// Connects to: src/Entity/BatchLot.php, src/Service/BatchLotManager.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\BatchLotRepository;
use App\Repository\ProductRepository;
use App\Service\BatchLotManager;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/batch-lots', name: 'api_v1_lots_')]
class BatchLotController extends AbstractController
{
    public function __construct(
        private readonly BatchLotRepository $batchLotRepository,
        private readonly ProductRepository $productRepository,
        private readonly BatchLotManager $batchLotManager,
        private readonly TokenAuthenticator $authenticator,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $productId = $request->query->get('product_id');
        $criteria = [];
        if ($productId) {
            $criteria['product'] = (int)$productId;
        }

        $lots = $this->batchLotRepository->findBy($criteria, ['expirationDate' => 'ASC']);
        $json = $this->serializer->serialize($lots, 'json', ['groups' => ['lot:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/expiring', name: 'expiring', methods: ['GET'])]
    public function expiring(Request $request): JsonResponse
    {
        $days = max(1, (int)$request->query->get('days', 30));
        $lots = $this->batchLotRepository->findExpiringLots($days);
        $json = $this->serializer->serialize($lots, 'json', ['groups' => ['lot:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['product_id'], $data['batch_number'], $data['quantity'], $data['expiration_date'])) {
            return $this->json(['error' => 'Required fields: product_id, batch_number, quantity, expiration_date'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find((int)$data['product_id']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $expDate = new \DateTimeImmutable($data['expiration_date']);
            $mfgDate = isset($data['manufacturing_date']) ? new \DateTimeImmutable($data['manufacturing_date']) : null;
            $qty = (int)$data['quantity'];

            $lot = $this->batchLotManager->createBatchLot($product, $data['batch_number'], $qty, $expDate, $mfgDate);
            $json = $this->serializer->serialize($lot, 'json', ['groups' => ['lot:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/fefo-fulfill', name: 'fefo_fulfill', methods: ['POST'])]
    public function fefoFulfill(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['product_id'], $data['quantity'])) {
            return $this->json(['error' => 'Required fields: product_id, quantity'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find((int)$data['product_id']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $allocations = $this->batchLotManager->allocateFefoStock($product, (int)$data['quantity']);

            return $this->json([
                'message' => sprintf('Successfully allocated %d units via FEFO strategy.', $data['quantity']),
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'allocations' => $allocations,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
