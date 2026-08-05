<?php

// src/EventSubscriber/TenantSubscriber.php
// Symfony KernelEvent subscriber resolving active tenant from X-Tenant-Code HTTP headers or User context.
// Connects to: src/Service/TenantContext.php, src/Repository/TenantRepository.php
// Created: 2026-08-05

namespace App\EventSubscriber;

use App\Repository\TenantRepository;
use App\Service\TenantContext;
use App\Service\TokenAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantRepository $tenantRepository,
        private readonly TokenAuthenticator $authenticator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $tenantCode = $request->headers->get('X-Tenant-Code');

        if ($tenantCode) {
            $tenant = $this->tenantRepository->findOneBy(['code' => strtoupper(trim($tenantCode)), 'active' => true]);
            if ($tenant) {
                $this->tenantContext->setCurrentTenant($tenant);
                return;
            }
        }

        // Fallback: Check if authenticated user belongs to a tenant organization
        $user = $this->authenticator->getUserFromRequest($request);
        if ($user && method_exists($user, 'getTenant') && $user->getTenant() !== null) {
            $this->tenantContext->setCurrentTenant($user->getTenant());
        }
    }
}
