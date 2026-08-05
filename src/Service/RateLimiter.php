<?php

// src/Service/RateLimiter.php
// Sliding window rate limiter tracking request quotas per client key (IP or Bearer token).
// Connects to: src/EventSubscriber/RateLimitSubscriber.php
// Created: 2026-08-05

namespace App\Service;

class RateLimiter
{
    private static array $storage = [];

    public function __construct(
        private readonly int $limit = 60,
        private readonly int $windowSeconds = 60
    ) {
    }

    /**
     * Evaluates rate limit status for a client key.
     * @return array { is_exceeded: bool, limit: int, remaining: int, reset: int }
     */
    public function consume(string $clientKey): array
    {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        if (!isset(self::$storage[$clientKey])) {
            self::$storage[$clientKey] = [];
        }

        // Filter out timestamps outside the sliding window
        self::$storage[$clientKey] = array_filter(
            self::$storage[$clientKey],
            fn(int $timestamp) => $timestamp > $windowStart
        );

        $currentCount = count(self::$storage[$clientKey]);
        $isExceeded = $currentCount >= $this->limit;

        if (!$isExceeded) {
            self::$storage[$clientKey][] = $now;
            $currentCount++;
        }

        $remaining = max(0, $this->limit - $currentCount);
        $resetTime = $now + $this->windowSeconds;

        return [
            'is_exceeded' => $isExceeded,
            'limit' => $this->limit,
            'remaining' => $remaining,
            'reset' => $resetTime,
        ];
    }

    public static function resetStorage(): void
    {
        self::$storage = [];
    }
}
