<?php

// src/Controller/WebhookRetryController.php
// REST API controller managing webhook retry queues and circuit breaker inspections.
// Connects to: src/Entity/WebhookRetryQueue.php, src/Service/WebhookRetryEngine.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\WebhookRetryQueueRepository;
use App\Service\TokenAuthenticator;
use App\Service\WebhookRetryEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/webhooks/retries', name: 'api_v1_webhook_retries_')]
class WebhookRetryController extends AbstractController
{
    public function __construct(
        private readonly WebhookRetryQueueRepository $retryQueueRepository,
        private readonly WebhookRetryEngine $retryEngine,
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

        $items = $this->retryQueueRepository->findBy($criteria, ['createdAt' => 'DESC'], 50);
        $json = $this->serializer->serialize($items, 'json', ['groups' => ['webhook_retry:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/process', name: 'process', methods: ['POST'])]
    public function process(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        $results = $this->retryEngine->processDueRetries();

        return $this->json($results, Response::HTTP_OK);
    }
}
