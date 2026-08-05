<?php

// tests/Service/TenantContextTest.php
// Unit tests for TenantContext and TenantSubscriber verifying multi-tenant resolution and context switching.
// Connects to: src/Service/TenantContext.php, src/Entity/Tenant.php, src/EventSubscriber/TenantSubscriber.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\Tenant;
use App\Entity\User;
use App\EventSubscriber\TenantSubscriber;
use App\Repository\TenantRepository;
use App\Service\TenantContext;
use App\Service\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TenantContextTest extends TestCase
{
    private TenantContext $tenantContext;
    private TenantRepository $tenantRepository;
    private TokenAuthenticator $authenticator;
    private TenantSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->tenantContext = new TenantContext();
        $this->tenantRepository = $this->createMock(TenantRepository::class);
        $this->authenticator = $this->createMock(TokenAuthenticator::class);

        $this->subscriber = new TenantSubscriber(
            $this->tenantContext,
            $this->tenantRepository,
            $this->authenticator
        );
    }

    public function testTenantContextStoresAndRetrievesTenant(): void
    {
        $tenant = new Tenant();
        $tenant->setCode('ACME-CORP');
        $tenant->setName('Acme Corporation');

        $this->tenantContext->setCurrentTenant($tenant);

        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertEquals('ACME-CORP', $this->tenantContext->getTenantCode());
        $this->assertSame($tenant, $this->tenantContext->getCurrentTenant());
    }

    public function testSubscriberResolvesTenantFromHeader(): void
    {
        $tenant = new Tenant();
        $tenant->setCode('GLOBEX');
        $tenant->setName('Globex Inc');

        $this->tenantRepository->method('findOneBy')
            ->with(['code' => 'GLOBEX', 'active' => true])
            ->willReturn($tenant);

        $request = new Request();
        $request->headers->set('X-Tenant-Code', 'GLOBEX');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertEquals('GLOBEX', $this->tenantContext->getTenantCode());
    }

    public function testSubscriberResolvesTenantFromAuthenticatedUser(): void
    {
        $tenant = new Tenant();
        $tenant->setCode('STARK');
        $tenant->setName('Stark Industries');

        $user = new User();
        $user->setTenant($tenant);

        $request = new Request();
        $this->authenticator->method('getUserFromRequest')->with($request)->willReturn($user);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertEquals('STARK', $this->tenantContext->getTenantCode());
    }
}
