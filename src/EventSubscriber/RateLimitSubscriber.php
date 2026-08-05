<?php

// src/EventSubscriber/RateLimitSubscriber.php
// Symfony KernelEvent subscriber enforcing API rate limits and injecting X-RateLimit headers.
// Connects to: src/Service/RateLimiter.php
// Created: 2026-08-05

namespace App\EventSubscriber;

use App\Service\RateLimiter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiter $rateLimiter
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api/v1')) {
            return;
        }

        // Determine client key: Use Bearer Token if present, otherwise fallback to Client IP
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $clientKey = 'token:' . md5(trim(substr($authHeader, 7)));
        } else {
            $clientKey = 'ip:' . ($request->getClientIp() ?? '127.0.0.1');
        }

        $status = $this->rateLimiter->consume($clientKey);
        $request->attributes->set('_rate_limit_status', $status);

        if ($status['is_exceeded']) {
            $response = new JsonResponse([
                'error' => 'Too Many Requests',
                'message' => sprintf('API rate limit exceeded (%d requests/min). Please try again in %d seconds.', $status['limit'], $status['reset'] - time()),
                'retry_after' => max(1, $status['reset'] - time()),
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->set('X-RateLimit-Limit', (string)$status['limit']);
            $response->headers->set('X-RateLimit-Remaining', (string)$status['remaining']);
            $response->headers->set('X-RateLimit-Reset', (string)$status['reset']);
            $response->headers->set('Retry-After', (string)max(1, $status['reset'] - time()));

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $status = $request->attributes->get('_rate_limit_status');

        if ($status && is_array($status)) {
            $response = $event->getResponse();
            $response->headers->set('X-RateLimit-Limit', (string)$status['limit']);
            $response->headers->set('X-RateLimit-Remaining', (string)$status['remaining']);
            $response->headers->set('X-RateLimit-Reset', (string)$status['reset']);
        }
    }
}
