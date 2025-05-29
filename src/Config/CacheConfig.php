<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Config;

readonly class CacheConfig
{
    public function __construct(
        public bool $enabled = true,
        public string $driver = 'file',
        public string $prefix = 'gmail_client',
        public int $defaultTtl = 300,
        public int $labelsTtl = 3600,
        public int $messagesTtl = 1800,
        public int $statisticsTtl = 900,
        public int $authTokensTtl = 3500,
        public bool $enableTagging = true,
        /** @var array<string> */
        public array $tags = ['gmail', 'api'],
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            enabled: $config['enabled'] ?? true,
            driver: $config['driver'] ?? 'file',
            prefix: $config['prefix'] ?? 'gmail_client',
            defaultTtl: $config['default_ttl'] ?? 300,
            labelsTtl: $config['labels_ttl'] ?? 3600,
            messagesTtl: $config['messages_ttl'] ?? 1800,
            statisticsTtl: $config['statistics_ttl'] ?? 900,
            authTokensTtl: $config['auth_tokens_ttl'] ?? 3500,
            enableTagging: $config['enable_tagging'] ?? true,
            tags: $config['tags'] ?? ['gmail', 'api'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'driver' => $this->driver,
            'prefix' => $this->prefix,
            'default_ttl' => $this->defaultTtl,
            'labels_ttl' => $this->labelsTtl,
            'messages_ttl' => $this->messagesTtl,
            'statistics_ttl' => $this->statisticsTtl,
            'auth_tokens_ttl' => $this->authTokensTtl,
            'enable_tagging' => $this->enableTagging,
            'tags' => $this->tags,
        ];
    }
}
