<?php

// src/Controller/RevisionController.php
// REST API controller managing event-sourced entity revision audit logs and state rollbacks.
// Connects to: src/Entity/EntityRevision.php, src/Service/AuditTrailEngine.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\EntityRevisionRepository;
use App\Service\AuditTrailEngine;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/revisions', name: 'api_v1_revisions_')]
class RevisionController extends AbstractController
{
    public function __construct(
        private readonly EntityRevisionRepository $revisionRepository,
        private readonly AuditTrailEngine $auditTrailEngine,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $entityClass = $request->query->get('entity_class');
        $entityId = $request->query->get('entity_id');

        $criteria = [];
        if ($entityClass) {
            $criteria['entityClass'] = $entityClass;
        }
        if ($entityId) {
            $criteria['entityId'] = (int)$entityId;
        }

        $revisions = $this->revisionRepository->findBy($criteria, ['createdAt' => 'DESC'], 50);
        $json = $this->serializer->serialize($revisions, 'json', ['groups' => ['revision:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/rollback', name: 'rollback', methods: ['POST'])]
    public function rollback(int $id, Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $restoredEntity = $this->auditTrailEngine->rollbackEntityToRevision($id);
            $json = $this->serializer->serialize($restoredEntity, 'json', ['groups' => ['product:read', 'user:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
