<?php

// src/Service/TokenAuthenticator.php
// Cryptographic token service issuing and verifying Bearer tokens for API authentication and RBAC.
// Connects to: src/Entity/User.php, src/Repository/UserRepository.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

class TokenAuthenticator
{
    private string $secret;

    public function __construct(
        private readonly UserRepository $userRepository,
        string $secret = '216bc920253457ad76e4c76b9f2913bf'
    ) {
        $this->secret = $secret;
    }

    /**
     * Generates a signed Bearer token payload for a user.
     */
    public function generateToken(User $user, int $ttlSeconds = 86400): string
    {
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'exp' => time() + $ttlSeconds,
        ];

        $json = json_encode($payload);
        $base64 = base64_encode($json);
        $signature = hash_hmac('sha256', $base64, $this->secret);

        return sprintf('%s.%s', $base64, $signature);
    }

    /**
     * Validates a Bearer token string and returns the associated User if valid.
     */
    public function authenticateToken(string $token): ?User
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$base64, $providedSignature] = $parts;
        $expectedSignature = hash_hmac('sha256', $base64, $this->secret);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $json = base64_decode($base64);
        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['sub'], $payload['exp'])) {
            return null;
        }

        if (time() > $payload['exp']) {
            return null;
        }

        return $this->userRepository->find((int)$payload['sub']);
    }

    /**
     * Extracts and authenticates User from HTTP request headers.
     */
    public function getUserFromRequest(Request $request): ?User
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authHeader, 7));
        return $this->authenticateToken($token);
    }
}
