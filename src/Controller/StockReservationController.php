<?php

// src/Controller/StockReservationController.php
// REST API controller managing temporary cart stock holds, confirmations, and cancellations.
// Connects to: src/Entity/StockReservation.php, src/Service/StockReservationEngine.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\StockReservationRepository;
use App\Service\StockReservationEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/reservations', name: 'api_v1_reservations_')]
class StockReservationController extends AbstractController
{
    public function __construct(
        private readonly StockReservationRepository $reservationRepository,
        private readonly ProductRepository $productRepository,
        private readonly StockReservationEngine $reservationEngine,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['product_id'], $data['quantity'])) {
            return $this->json(['error' => 'Required fields: product_id, quantity'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find((int)$data['product_id']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $quantity = (int)$data['quantity'];
        $ttl = isset($data['ttl_minutes']) ? (int)$data['ttl_minutes'] : 15;
        $sessionKey = $data['session_key'] ?? null;

        try {
            $reservation = $this->reservationEngine->reserveStock($product, $quantity, $ttl, $sessionKey);
            $json = $this->serializer->serialize($reservation, 'json', ['groups' => ['reservation:read']]);

            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $reservation = $this->reservationRepository->findOneBy(['reservationToken' => strtoupper(trim($token))]);
        if (!$reservation) {
            return $this->json(['error' => 'Reservation token not found'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($reservation, 'json', ['groups' => ['reservation:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{token}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(string $token): JsonResponse
    {
        try {
            $reservation = $this->reservationEngine->confirmReservation($token);
            $json = $this->serializer->serialize($reservation, 'json', ['groups' => ['reservation:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{token}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(string $token): JsonResponse
    {
        try {
            $reservation = $this->reservationEngine->cancelReservation($token);
            $json = $this->serializer->serialize($reservation, 'json', ['groups' => ['reservation:read']]);

            return new JsonResponse($json, Response::HTTP_OK, [], true);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
