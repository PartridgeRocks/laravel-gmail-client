<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Config;

readonly class LoggingConfig
{
    public function __construct(
        public bool $enabled = true,
        public string $channel = 'gmail-client',
        public string $level = 'info',
        public bool $logRequests = false,
        public bool $logResponses = false,
        public bool $logErrors = true,
        public bool $logPerformance = false,
        public array $sensitiveFields = ['access_token', 'refresh_token', 'client_secret'],
        public bool $enableContextLogging = true,
        public int $maxLogSize = 1024,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? true,
            channel: $config['channel'] ?? 'gmail-client',
            level: $config['level'] ?? 'info',
            logRequests: $config['log_requests'] ?? false,
            logResponses: $config['log_responses'] ?? false,
            logErrors: $config['log_errors'] ?? true,
            logPerformance: $config['log_performance'] ?? false,
            sensitiveFields: $config['sensitive_fields'] ?? ['access_token', 'refresh_token', 'client_secret'],
            enableContextLogging: $config['enable_context_logging'] ?? true,
            maxLogSize: $config['max_log_size'] ?? 1024,
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'channel' => $this->channel,
            'level' => $this->level,
            'log_requests' => $this->logRequests,
            'log_responses' => $this->logResponses,
            'log_errors' => $this->logErrors,
            'log_performance' => $this->logPerformance,
            'sensitive_fields' => $this->sensitiveFields,
            'enable_context_logging' => $this->enableContextLogging,
            'max_log_size' => $this->maxLogSize,
        ];
    }
}
