<?php

// src/Service/TenantContext.php
// In-memory tenant context service storing and providing the active tenant organization per HTTP request.
// Connects to: src/Entity/Tenant.php, src/EventSubscriber/TenantSubscriber.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\Tenant;

class TenantContext
{
    private ?Tenant $currentTenant = null;

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    public function getTenantCode(): ?string
    {
        return $this->currentTenant?->getCode();
    }
}
