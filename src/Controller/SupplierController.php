<?php

// src/Controller/SupplierController.php
// REST API controller managing Suppliers.
// Connects to: src/Entity/Supplier.php, src/Repository/SupplierRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Supplier;
use App\Entity\User;
use App\Repository\SupplierRepository;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/suppliers', name: 'api_v1_suppliers_')]
class SupplierController extends AbstractController
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository,
        private readonly TokenAuthenticator $authenticator,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $suppliers = $this->supplierRepository->findBy([], ['name' => 'ASC']);
        $json = $this->serializer->serialize($suppliers, 'json', ['groups' => ['supplier:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $supplier = new Supplier();
        $supplier->setCode($data['code'] ?? '');
        $supplier->setName($data['name'] ?? '');
        $supplier->setContactEmail($data['contact_email'] ?? null);
        if (isset($data['lead_time_days'])) {
            $supplier->setLeadTimeDays((int)$data['lead_time_days']);
        }

        $errors = $this->validator->validate($supplier);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->supplierRepository->save($supplier, true);
        $json = $this->serializer->serialize($supplier, 'json', ['groups' => ['supplier:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
}
