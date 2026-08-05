<?php

// src/Kernel.php
// Symfony microkernel configuring bundles, routes, and environment containers.
// Connects to: config/bundles.php, config/routes.yaml, config/services.yaml
// Created: 2026-08-05

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
