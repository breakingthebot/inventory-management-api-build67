<?php

// src/Controller/ProductVariantController.php
// REST API controller managing child product variant SKUs and per-variant inventory levels.
// Connects to: src/Entity/ProductVariant.php, src/Service/VariantManager.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use App\Service\TokenAuthenticator;
use App\Service\VariantManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductVariantController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly VariantManager $variantManager,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/api/v1/products/{id}/variants', name: 'api_v1_product_variants_index', methods: ['GET'])]
    public function index(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $variants = $this->variantRepository->findBy(['parentProduct' => $product], ['createdAt' => 'ASC']);
        $json = $this->serializer->serialize($variants, 'json', ['groups' => ['variant:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/products/{id}/variants', name: 'api_v1_product_variants_create', methods: ['POST'])]
    public function create(int $id, Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['sku'], $data['option_values']) || !is_array($data['option_values'])) {
            return $this->json(['error' => 'Required fields: sku, option_values (array)'], Response::HTTP_BAD_REQUEST);
        }

        $priceOverride = isset($data['price_override']) ? (float)$data['price_override'] : null;
        $initialStock = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
        $minStockLevel = isset($data['min_stock_level']) ? (int)$data['min_stock_level'] : 5;

        try {
            $variant = $this->variantManager->createVariant(
                $product,
                $data['sku'],
                $data['option_values'],
                $priceOverride,
                $initialStock,
                $minStockLevel
            );

            $json = $this->serializer->serialize($variant, 'json', ['groups' => ['variant:read']]);
            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/api/v1/variants/{id}/stock', name: 'api_v1_variant_stock_adjust', methods: ['POST'])]
    public function adjustStock(int $id, Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $variant = $this->variantRepository->find($id);
        if (!$variant) {
            return $this->json(['error' => 'Variant not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['quantity'])) {
            return $this->json(['error' => 'Required field: quantity'], Response::HTTP_BAD_REQUEST);
        }

        $quantity = (int)$data['quantity'];
        $type = strtoupper($data['type'] ?? 'IN');

        try {
            $updated = $this->variantManager->adjustVariantStock($variant, $quantity, $type);
            $json = $this->serializer->serialize($updated, 'json', ['groups' => ['variant:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
