<?php

// tests/Service/RateLimiterTest.php
// Unit tests for RateLimiter service verifying sliding window quota tracking and 429 throttle limits.
// Connects to: src/Service/RateLimiter.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Service\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        RateLimiter::resetStorage();
    }

    public function testRateLimiterAllowsRequestsUnderLimit(): void
    {
        $limiter = new RateLimiter(5, 60);

        for ($i = 1; $i <= 5; $i++) {
            $status = $limiter->consume('client_test_key');
            $this->assertFalse($status['is_exceeded']);
            $this->assertEquals(5 - $i, $status['remaining']);
        }
    }

    public function testRateLimiterBlocksRequestsExceedingLimit(): void
    {
        $limiter = new RateLimiter(3, 60);

        // First 3 requests allowed
        $limiter->consume('client_key_2');
        $limiter->consume('client_key_2');
        $status3 = $limiter->consume('client_key_2');

        $this->assertFalse($status3['is_exceeded']);
        $this->assertEquals(0, $status3['remaining']);

        // 4th request blocked with 429 is_exceeded flag
        $status4 = $limiter->consume('client_key_2');
        $this->assertTrue($status4['is_exceeded']);
        $this->assertEquals(0, $status4['remaining']);
    }
}
