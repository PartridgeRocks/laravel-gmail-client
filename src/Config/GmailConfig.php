<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Config;

readonly class GmailConfig
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public array $scopes,
        public ?string $fromEmail = null,
        public bool $storeTokensInDatabase = false,
        public PerformanceConfig $performance = new PerformanceConfig,
        public CacheConfig $cache = new CacheConfig,
        public LoggingConfig $logging = new LoggingConfig,
        public RateLimitConfig $rateLimit = new RateLimitConfig,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            clientId: $config['client_id'] ?? throw new \InvalidArgumentException('Gmail client_id is required'),
            clientSecret: $config['client_secret'] ?? throw new \InvalidArgumentException('Gmail client_secret is required'),
            redirectUri: $config['redirect_uri'] ?? throw new \InvalidArgumentException('Gmail redirect_uri is required'),
            scopes: $config['scopes'] ?? [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.compose',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.labels',
            ],
            fromEmail: $config['from_email'] ?? null,
            storeTokensInDatabase: $config['store_tokens_in_database'] ?? false,
            performance: PerformanceConfig::fromArray($config['performance'] ?? []),
            cache: CacheConfig::fromArray($config['cache'] ?? []),
            logging: LoggingConfig::fromArray($config['logging'] ?? []),
            rateLimit: RateLimitConfig::fromArray($config['rate_limit'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'scopes' => $this->scopes,
            'from_email' => $this->fromEmail,
            'store_tokens_in_database' => $this->storeTokensInDatabase,
            'performance' => $this->performance->toArray(),
            'cache' => $this->cache->toArray(),
            'logging' => $this->logging->toArray(),
            'rate_limit' => $this->rateLimit->toArray(),
        ];
    }
}
