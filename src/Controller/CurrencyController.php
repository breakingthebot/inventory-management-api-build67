<?php

// src/Controller/CurrencyController.php
// REST API controller managing multi-currency exchange rates, tax zones, and converted pricing lookups.
// Connects to: src/Service/CurrencyConverter.php, src/Repository/ProductRepository.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\CurrencyRate;
use App\Entity\User;
use App\Repository\CurrencyRateRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxZoneRepository;
use App\Service\CurrencyConverter;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1', name: 'api_v1_currency_')]
class CurrencyController extends AbstractController
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository,
        private readonly TaxZoneRepository $taxZoneRepository,
        private readonly ProductRepository $productRepository,
        private readonly CurrencyConverter $currencyConverter,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/currencies', name: 'list', methods: ['GET'])]
    public function listCurrencies(): JsonResponse
    {
        $this->currencyConverter->ensureSeededRatesAndTaxZones();
        $rates = $this->currencyRateRepository->findBy([], ['code' => 'ASC']);
        $json = $this->serializer->serialize($rates, 'json', ['groups' => ['currency:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/tax-zones', name: 'list_tax_zones', methods: ['GET'])]
    public function listTaxZones(): JsonResponse
    {
        $this->currencyConverter->ensureSeededRatesAndTaxZones();
        $zones = $this->taxZoneRepository->findBy([], ['code' => 'ASC']);
        $json = $this->serializer->serialize($zones, 'json', ['groups' => ['tax:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/currencies/update', name: 'update_rate', methods: ['POST'])]
    public function updateRate(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user || !in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return $this->json(['error' => 'Access denied. Requires ROLE_ADMIN.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['code'], $data['rate_to_base'])) {
            return $this->json(['error' => 'Fields "code" and "rate_to_base" are required.'], Response::HTTP_BAD_REQUEST);
        }

        $code = strtoupper(trim($data['code']));
        $rate = (float)$data['rate_to_base'];

        $currency = $this->currencyRateRepository->findOneBy(['code' => $code]);
        if (!$currency) {
            $currency = new CurrencyRate();
            $currency->setCode($code);
            $currency->setSymbol($data['symbol'] ?? $code);
        }

        $currency->setRateToBase($rate);
        $this->currencyRateRepository->save($currency, true);

        $json = $this->serializer->serialize($currency, 'json', ['groups' => ['currency:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/products/{id}/price', name: 'product_price', methods: ['GET'])]
    public function getProductPrice(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $currency = $request->query->get('currency', 'USD');
        $taxZone = $request->query->get('tax_zone');

        $priceResult = $this->currencyConverter->convertAndCalculateTax(
            (float)$product->getUnitPrice(),
            $currency,
            $taxZone
        );

        return $this->json([
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'pricing' => $priceResult,
        ], Response::HTTP_OK);
    }
}
