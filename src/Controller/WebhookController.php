<?php

// src/Controller/WebhookController.php
// REST API controller managing webhook subscriptions and notification delivery audit logs.
// Connects to: src/Entity/WebhookSubscription.php, src/Entity/NotificationLog.php, src/Repository/WebhookSubscriptionRepository.php, src/Repository/NotificationLogRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\WebhookSubscription;
use App\Repository\NotificationLogRepository;
use App\Repository\WebhookSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1', name: 'api_v1_webhooks_')]
class WebhookController extends AbstractController
{
    public function __construct(
        private readonly WebhookSubscriptionRepository $webhookRepository,
        private readonly NotificationLogRepository $notificationLogRepository,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/webhooks/subscriptions', name: 'list_subscriptions', methods: ['GET'])]
    public function listSubscriptions(): JsonResponse
    {
        $subscriptions = $this->webhookRepository->findBy([], ['createdAt' => 'DESC']);
        $json = $this->serializer->serialize($subscriptions, 'json', ['groups' => ['webhook:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/webhooks/subscriptions', name: 'create_subscription', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['url'])) {
            return $this->json(['error' => 'Webhook URL is required'], Response::HTTP_BAD_REQUEST);
        }

        $subscription = new WebhookSubscription();
        $subscription->setUrl($data['url']);
        if (isset($data['event_filter'])) {
            $subscription->setEventFilter($data['event_filter']);
        }

        $errors = $this->validator->validate($subscription);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->webhookRepository->save($subscription, true);
        $json = $this->serializer->serialize($subscription, 'json', ['groups' => ['webhook:read']]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/webhooks/subscriptions/{id}', name: 'delete_subscription', methods: ['DELETE'])]
    public function deleteSubscription(int $id): JsonResponse
    {
        $subscription = $this->webhookRepository->find($id);
        if (!$subscription) {
            return $this->json(['error' => 'Webhook subscription not found'], Response::HTTP_NOT_FOUND);
        }

        $this->webhookRepository->remove($subscription, true);

        return $this->json(['message' => 'Webhook subscription deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/notifications/logs', name: 'notification_logs', methods: ['GET'])]
    public function notificationLogs(): JsonResponse
    {
        $logs = $this->notificationLogRepository->findBy([], ['createdAt' => 'DESC'], 50);
        $json = $this->serializer->serialize($logs, 'json', ['groups' => ['log:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
