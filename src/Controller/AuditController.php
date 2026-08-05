<?php

// src/Controller/AuditController.php
// REST API controller managing audit sampling sessions and count reconciliations.
// Connects to: src/Entity/AuditCycle.php, src/Service/AuditManager.php, src/Repository/WarehouseRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\AuditCycleRepository;
use App\Repository\WarehouseRepository;
use App\Service\AuditManager;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/audits', name: 'api_v1_audits_')]
class AuditController extends AbstractController
{
    public function __construct(
        private readonly AuditCycleRepository $auditCycleRepository,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly AuditManager $auditManager,
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

        $audits = $this->auditCycleRepository->findBy($criteria, ['createdAt' => 'DESC']);
        $json = $this->serializer->serialize($audits, 'json', ['groups' => ['audit:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $audit = $this->auditCycleRepository->find($id);
        if (!$audit) {
            return $this->json(['error' => 'Audit cycle not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($audit, 'json', ['groups' => ['audit:read', 'audit:detail', 'product:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $warehouse = $warehouseId ? $this->warehouseRepository->find($warehouseId) : null;

        $sampleSize = isset($data['sample_size']) ? (int)$data['sample_size'] : 5;
        $notes = $data['notes'] ?? null;

        try {
            $cycle = $this->auditManager->createAuditCycle($warehouse, $sampleSize, $notes);
            $json = $this->serializer->serialize($cycle, 'json', ['groups' => ['audit:read', 'audit:detail', 'product:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/reconcile', name: 'reconcile', methods: ['POST'])]
    public function reconcile(int $id, Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) && !in_array(User::ROLE_WAREHOUSE, $user->getRoles(), true))) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN or ROLE_WAREHOUSE.'], Response::HTTP_FORBIDDEN);
        }

        $audit = $this->auditCycleRepository->find($id);
        if (!$audit) {
            return $this->json(['error' => 'Audit cycle not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['counts']) || !is_array($data['counts'])) {
            return $this->json(['error' => 'Field "counts" array is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $cycle = $this->auditManager->reconcileAuditCycle($audit, $data['counts']);
            $json = $this->serializer->serialize($cycle, 'json', ['groups' => ['audit:read', 'audit:detail', 'product:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
