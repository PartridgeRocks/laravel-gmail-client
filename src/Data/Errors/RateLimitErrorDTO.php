<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

use Spatie\LaravelData\Data;

class RateLimitErrorDTO extends ErrorDTO
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
        public ?array $context = null,
        public ?string $service = 'Gmail API',
        public ?int $retryAfter = null,
        public ?int $quota = null,
        public ?string $quotaPeriod = null
    ) {
        parent::__construct($code, $message, $detail, $context, $service);
    }

    /**
     * Create a rate limit error DTO with retry information
     */
    public static function withRetry(int $retryAfter, ?string $detail = null, ?array $context = null): self
    {
        return new self(
            code: 'rate_limit_exceeded',
            message: 'Rate limit exceeded for Gmail API',
            detail: $detail ?? 'Please wait before retrying this request',
            context: $context,
            retryAfter: $retryAfter
        );
    }

    /**
     * Create a quota exceeded error DTO
     */
    public static function quotaExceeded(int $quota, string $period = 'day', ?string $detail = null, ?array $context = null): self
    {
        return new self(
            code: 'quota_exceeded',
            message: "API quota exceeded ({$quota} per {$period})",
            detail: $detail,
            context: $context,
            quota: $quota,
            quotaPeriod: $period
        );
    }
}