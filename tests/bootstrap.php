<?php

// tests/bootstrap.php
// PHPUnit test suite bootstrapper loading autoloaders and environment variables.
// Connects to: vendor/autoload.php, .env
// Created: 2026-08-05

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
