<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Config;

readonly class RateLimitConfig
{
    public function __construct(
        public bool $enabled = true,
        public int $requestsPerSecond = 10,
        public int $requestsPerMinute = 250,
        public int $requestsPerDay = 1000000,
        public bool $enableBackoff = true,
        public int $initialBackoffSeconds = 1,
        public int $maxBackoffSeconds = 64,
        public float $backoffMultiplier = 2.0,
        public int $maxRetries = 3,
        public bool $enableJitter = true,
        public array $exemptOperations = ['auth.refresh'],
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? true,
            requestsPerSecond: $config['requests_per_second'] ?? 10,
            requestsPerMinute: $config['requests_per_minute'] ?? 250,
            requestsPerDay: $config['requests_per_day'] ?? 1000000,
            enableBackoff: $config['enable_backoff'] ?? true,
            initialBackoffSeconds: $config['initial_backoff_seconds'] ?? 1,
            maxBackoffSeconds: $config['max_backoff_seconds'] ?? 64,
            backoffMultiplier: $config['backoff_multiplier'] ?? 2.0,
            maxRetries: $config['max_retries'] ?? 3,
            enableJitter: $config['enable_jitter'] ?? true,
            exemptOperations: $config['exempt_operations'] ?? ['auth.refresh'],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'requests_per_second' => $this->requestsPerSecond,
            'requests_per_minute' => $this->requestsPerMinute,
            'requests_per_day' => $this->requestsPerDay,
            'enable_backoff' => $this->enableBackoff,
            'initial_backoff_seconds' => $this->initialBackoffSeconds,
            'max_backoff_seconds' => $this->maxBackoffSeconds,
            'backoff_multiplier' => $this->backoffMultiplier,
            'max_retries' => $this->maxRetries,
            'enable_jitter' => $this->enableJitter,
            'exempt_operations' => $this->exemptOperations,
        ];
    }
}
