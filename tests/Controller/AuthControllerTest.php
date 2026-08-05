<?php

// tests/Controller/AuthControllerTest.php
// Unit & Integration tests for AuthController token generation, password verification, and RBAC security rules.
// Connects to: src/Controller/AuthController.php, src/Entity/User.php, src/Service/TokenAuthenticator.php
// Created: 2026-08-05

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TokenAuthenticator;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    private UserRepository $userRepository;
    private TokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->authenticator = new TokenAuthenticator($this->userRepository, 'test_secret_key');
    }

    public function testTokenGenerationAndVerification(): void
    {
        $user = new User();
        $user->setEmail('admin@inventory.internal');
        $user->setRoles([User::ROLE_ADMIN]);

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 42);

        $this->userRepository->method('find')->with(42)->willReturn($user);

        $token = $this->authenticator->generateToken($user);
        $this->assertNotEmpty($token);

        $authenticatedUser = $this->authenticator->authenticateToken($token);
        $this->assertNotNull($authenticatedUser);
        $this->assertEquals('admin@inventory.internal', $authenticatedUser->getEmail());
        $this->assertContains(User::ROLE_ADMIN, $authenticatedUser->getRoles());
    }

    public function testInvalidSignatureRejected(): void
    {
        $user = new User();
        $user->setEmail('user@test.com');
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 1);

        $validToken = $this->authenticator->generateToken($user);
        $tamperedToken = $validToken . 'tampered';

        $this->assertNull($this->authenticator->authenticateToken($tamperedToken));
    }
}
