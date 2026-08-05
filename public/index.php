<?php

// public/index.php
// Front controller entry point handling incoming HTTP requests.
// Connects to: src/Kernel.php, vendor/autoload.php
// Created: 2026-08-05

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
