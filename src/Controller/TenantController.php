<?php

// src/Controller/TenantController.php
// REST API controller managing multi-tenant business organization provisioning.
// Connects to: src/Entity/Tenant.php, src/Repository/TenantRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\TenantRepository;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/tenants', name: 'api_v1_tenants_')]
class TenantController extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $tenants = $this->tenantRepository->findBy([], ['createdAt' => 'DESC']);
        $json = $this->serializer->serialize($tenants, 'json', ['groups' => ['tenant:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = $this->serializer->deserialize($request->getContent(), Tenant::class, 'json', ['groups' => ['tenant:write']]);
        $errors = $this->validator->validate($tenant);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string)$errors], Response::HTTP_BAD_REQUEST);
        }

        $this->tenantRepository->save($tenant, true);
        $json = $this->serializer->serialize($tenant, 'json', ['groups' => ['tenant:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
}
