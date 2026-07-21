<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use InvalidArgumentException;

/**
 * Token bucket rate limiter for ORCID API.
 *
 * ORCID limits: 24 requests/second sustained, 40 burst capacity
 * Implements a token bucket algorithm to stay within these limits
 */
final class OrcidRateLimiter
{
    private float $tokens;
    private float $lastRefillAt;

    public function __construct(
        private readonly float $requestsPerSecond = 24.0,
        private readonly int $burstCapacity = 40
    ) {
        // Start with full bucket to allow immediate burst
        $this->tokens = (float)$burstCapacity;
        $this->lastRefillAt = microtime(true);
    }

    /**
     * Throttle request execution to stay within rate limits.
     *
     * This method will block (sleep) if insufficient tokens are available,
     * waiting until enough tokens have been refilled.
     *
     * @param int $cost Number of tokens to consume (default 1 per request)
     */
    public function throttle(int $cost = 1): void
    {
        if ($cost <= 0) {
            return;
        }

        // Ensure we don't try to consume more than burst capacity
        if ($cost > $this->burstCapacity) {
            throw new InvalidArgumentException(
                "Cannot consume {$cost} tokens - exceeds burst capacity of {$this->burstCapacity}"
            );
        }

        while (true) {
            $this->refill();

            if ($this->tokens >= $cost) {
                $this->tokens -= $cost;

                return;
            }

            // Calculate how long to wait for enough tokens
            $tokensNeeded = $cost - $this->tokens;
            $secondsToWait = $tokensNeeded / $this->requestsPerSecond;

            // Sleep at least 1ms to avoid busy loop
            $microsToWait = (int)max(1_000, ceil($secondsToWait * 1_000_000));
            usleep($microsToWait);
        }
    }

    /**
     * Refill tokens based on elapsed time since the last refill.
     *
     * Uses token bucket algorithm: tokens refill at constant rate
     * up to the maximum burst capacity
     */
    private function refill(): void
    {
        $now = microtime(true);
        $elapsed = $now - $this->lastRefillAt;

        if ($elapsed <= 0) {
            return;
        }

        $this->lastRefillAt = $now;

        // Add tokens based on elapsed time, capped at burst capacity
        $tokensToAdd = $elapsed * $this->requestsPerSecond;
        $this->tokens = min(
            (float)$this->burstCapacity,
            $this->tokens + $tokensToAdd
        );
    }
}
