<?php

// src/Service/StockReservationEngine.php
// Domain service calculating available unreserved stock, holding cart reservations, and confirming checkouts.
// Connects to: src/Entity/StockReservation.php, src/Entity/Product.php, src/Service/StockManager.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\StockReservation;
use App\Repository\StockReservationRepository;

class StockReservationEngine
{
    public function __construct(
        private readonly StockReservationRepository $reservationRepository,
        private readonly StockManager $stockManager
    ) {
    }

    /**
     * Returns total unreserved stock available for new reservations or immediate purchase.
     */
    public function getAvailableStock(Product $product): int
    {
        $this->reservationRepository->releaseExpiredReservations();
        $totalPhysical = $product->getStockQuantity();
        $held = $this->reservationRepository->getReservedQuantitySum($product);

        return max(0, $totalPhysical - $held);
    }

    /**
     * Reserves stock for a specified TTL window.
     */
    public function reserveStock(
        Product $product,
        int $quantity,
        int $ttlMinutes = 15,
        ?string $sessionKey = null
    ): StockReservation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        $available = $this->getAvailableStock($product);
        if ($available < $quantity) {
            throw new \InvalidArgumentException(sprintf(
                'Insufficient unreserved stock for SKU "%s". Physical: %d, Held: %d, Available: %d, Requested: %d.',
                $product->getSku(),
                $product->getStockQuantity(),
                $product->getStockQuantity() - $available,
                $available,
                $quantity
            ));
        }

        $reservation = new StockReservation();
        $reservation->setProduct($product);
        $reservation->setQuantity($quantity);
        $reservation->setReservationToken('RES-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
        $reservation->setExpiresAt((new \DateTimeImmutable())->modify(sprintf('+%d minutes', max(1, $ttlMinutes))));
        $reservation->setStatus(StockReservation::STATUS_RESERVED);
        $reservation->setSessionKey($sessionKey);

        $this->reservationRepository->save($reservation, true);

        return $reservation;
    }

    /**
     * Confirms reservation on order completion and deducts actual inventory stock.
     */
    public function confirmReservation(string $reservationToken): StockReservation
    {
        $reservation = $this->reservationRepository->findOneBy(['reservationToken' => strtoupper(trim($reservationToken))]);
        if (!$reservation) {
            throw new \InvalidArgumentException('Stock reservation token not found.');
        }

        if ($reservation->isExpired()) {
            throw new \InvalidArgumentException('Stock reservation has expired and cannot be confirmed.');
        }

        if ($reservation->getStatus() === StockReservation::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Stock reservation has already been confirmed.');
        }

        $reservation->setStatus(StockReservation::STATUS_CONFIRMED);
        $this->reservationRepository->save($reservation, false);

        // Deduct actual physical stock via StockManager
        $this->stockManager->recordMovement(
            $reservation->getProduct(),
            StockMovement::TYPE_OUT,
            $reservation->getQuantity(),
            sprintf('E-commerce order checkout confirmed (%s)', $reservation->getReservationToken()),
            $reservation->getReservationToken()
        );

        return $reservation;
    }

    /**
     * Cancels an active stock reservation and releases held inventory.
     */
    public function cancelReservation(string $reservationToken): StockReservation
    {
        $reservation = $this->reservationRepository->findOneBy(['reservationToken' => strtoupper(trim($reservationToken))]);
        if (!$reservation) {
            throw new \InvalidArgumentException('Stock reservation token not found.');
        }

        if ($reservation->getStatus() !== StockReservation::STATUS_RESERVED) {
            throw new \InvalidArgumentException(sprintf('Reservation cannot be cancelled in state "%s".', $reservation->getStatus()));
        }

        $reservation->setStatus(StockReservation::STATUS_RELEASED);
        $this->reservationRepository->save($reservation, true);

        return $reservation;
    }
}
