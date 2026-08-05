<?php

// src/Controller/ProductController.php
// REST API controller providing Full CRUD for Inventory Products with search, filtering, and validation.
// Connects to: src/Entity/Product.php, src/Entity/Category.php, src/Repository/ProductRepository.php, src/Repository/CategoryRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/products', name: 'api_v1_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $search = $request->query->get('q');
        $categoryId = $request->query->get('category_id') ? (int)$request->query->get('category_id') : null;
        $status = $request->query->get('status');
        $limit = max(1, min(100, (int)$request->query->get('limit', 50)));
        $offset = max(0, (int)$request->query->get('offset', 0));

        $products = $this->productRepository->findByFilters($search, $categoryId, $status, $limit, $offset);
        $json = $this->serializer->serialize($products, 'json', ['groups' => ['product:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($product, 'json', ['groups' => ['product:read', 'product:detail']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $sku = strtoupper(trim($data['sku'] ?? ''));
        if ($sku === '') {
            return $this->json(['error' => 'Product SKU is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = $this->productRepository->findOneBy(['sku' => $sku]);
        if ($existing) {
            return $this->json(['error' => sprintf('Product with SKU "%s" already exists', $sku)], Response::HTTP_CONFLICT);
        }

        $product = new Product();
        $product->setSku($sku);
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? null);
        $product->setUnitPrice($data['unit_price'] ?? $data['unitPrice'] ?? 0.0);
        $product->setStockQuantity((int)($data['stock_quantity'] ?? $data['stockQuantity'] ?? 0));
        $product->setMinStockLevel((int)($data['min_stock_level'] ?? $data['minStockLevel'] ?? 5));

        if (isset($data['category_id'])) {
            $category = $this->categoryRepository->find((int)$data['category_id']);
            if ($category) {
                $product->setCategory($category);
            }
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->productRepository->save($product, true);
        $json = $this->serializer->serialize($product, 'json', ['groups' => ['product:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['unit_price']) || isset($data['unitPrice'])) {
            $product->setUnitPrice($data['unit_price'] ?? $data['unitPrice']);
        }
        if (isset($data['min_stock_level']) || isset($data['minStockLevel'])) {
            $product->setMinStockLevel((int)($data['min_stock_level'] ?? $data['minStockLevel']));
        }
        if (isset($data['status'])) {
            $product->setStatus($data['status']);
        }
        if (array_key_exists('category_id', $data)) {
            $category = $data['category_id'] !== null ? $this->categoryRepository->find((int)$data['category_id']) : null;
            $product->setCategory($category);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->productRepository->save($product, true);
        $json = $this->serializer->serialize($product, 'json', ['groups' => ['product:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->productRepository->remove($product, true);

        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
