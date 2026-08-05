<?php

// src/Controller/AuthController.php
// REST API controller managing user authentication, login token issuance, and profile inspection.
// Connects to: src/Entity/User.php, src/Repository/UserRepository.php, src/Service/TokenAuthenticator.php
// Created: 2026-08-05

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TokenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenAuthenticator $authenticator,
        private readonly SerializerInterface $serializer
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $this->ensureSeededUsers();

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Fields "email" and "password" are required.'], Response::HTTP_BAD_REQUEST);
        }

        $email = strtolower(trim($data['email']));
        $password = $data['password'];

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->json(['error' => 'Invalid email credentials or password.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->authenticator->generateToken($user);
        $userJson = $this->serializer->serialize($user, 'json', ['groups' => ['user:read']]);

        return new JsonResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'user' => json_decode($userJson, true),
        ], Response::HTTP_OK);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $user = $this->authenticator->getUserFromRequest($request);
        if (!$user) {
            return $this->json(['error' => 'Authentication required. Missing or invalid Bearer token.'], Response::HTTP_UNAUTHORIZED);
        }

        $json = $this->serializer->serialize($user, 'json', ['groups' => ['user:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    private function ensureSeededUsers(): void
    {
        if ($this->userRepository->count([]) > 0) {
            return;
        }

        $admin = new User();
        $admin->setEmail('admin@inventory.internal');
        $admin->setPassword(password_hash('AdminPass123!', PASSWORD_BCRYPT));
        $admin->setFullName('System Administrator');
        $admin->setRoles([User::ROLE_ADMIN]);
        $this->userRepository->save($admin, false);

        $worker = new User();
        $worker->setEmail('warehouse@inventory.internal');
        $worker->setPassword(password_hash('WorkerPass123!', PASSWORD_BCRYPT));
        $worker->setFullName('Warehouse Operations Worker');
        $worker->setRoles([User::ROLE_WAREHOUSE]);
        $this->userRepository->save($worker, false);

        $auditor = new User();
        $auditor->setEmail('auditor@inventory.internal');
        $auditor->setPassword(password_hash('AuditorPass123!', PASSWORD_BCRYPT));
        $auditor->setFullName('Inventory Auditor');
        $auditor->setRoles([User::ROLE_VIEWER]);
        $this->userRepository->save($auditor, true);
    }
}
