<?php

// src/Controller/CategoryController.php
// REST API controller providing CRUD operations for Categories with validation and serialization.
// Connects to: src/Entity/Category.php, src/Repository/CategoryRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/categories', name: 'api_v1_categories_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findBy([], ['name' => 'ASC']);
        $json = $this->serializer->serialize($categories, 'json', ['groups' => ['category:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($category, 'json', ['groups' => ['category:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return $this->json(['error' => 'Category name is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existing = $this->categoryRepository->findOneBy(['name' => $name]);
        if ($existing) {
            return $this->json(['error' => 'A category with this name already exists'], Response::HTTP_CONFLICT);
        }

        $category = new Category();
        $category->setName($name);
        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->categoryRepository->save($category, true);
        $json = $this->serializer->serialize($category, 'json', ['groups' => ['category:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
}
