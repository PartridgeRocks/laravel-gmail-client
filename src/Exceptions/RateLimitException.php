<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use PartridgeRocks\GmailClient\Data\Errors\RateLimitErrorDTO;

class RateLimitException extends GmailClientException
{
    protected int $retryAfter;

    public function __construct(string $message, int $retryAfter = 0, ?RateLimitErrorDTO $error = null)
    {
        parent::__construct($message, 429, null, $error);
        $this->retryAfter = $retryAfter;
    }

    public static function quotaExceeded(int $retryAfter = 0): self
    {
        $error = RateLimitErrorDTO::withRetry(
            $retryAfter,
            'You have exceeded the Gmail API rate limit. Please try again later.'
        );

        return new self(
            'You have exceeded the Gmail API rate limit. Please try again later.',
            $retryAfter,
            $error
        );
    }

    /**
     * Create a rate limit exception from a daily quota error
     */
    public static function dailyQuotaExceeded(int $quota = 0): self
    {
        $error = RateLimitErrorDTO::quotaExceeded(
            $quota,
            'day',
            'Daily quota exceeded for Gmail API. Please try again tomorrow.'
        );

        return new self(
            'Daily quota exceeded for Gmail API. Please try again tomorrow.',
            86400, // Retry after 24 hours
            $error
        );
    }

    /**
     * Create a rate limit exception from a response with retry information
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromRateLimitResponse(array $response, int $retryAfter = 0): self
    {
        $errorData = $response['error'] ?? $response;
        $message = $errorData['message'] ?? 'Rate limit exceeded';

        // Check if it's a quota limit or rate limit
        $isQuota = false;
        if (isset($errorData['reason']) && $errorData['reason'] === 'quotaExceeded') {
            $isQuota = true;
        }

        if ($isQuota) {
            $error = RateLimitErrorDTO::quotaExceeded(
                $errorData['quota'] ?? 0,
                'day',
                $message,
                $response
            );
        } else {
            $error = RateLimitErrorDTO::withRetry(
                $retryAfter,
                $message,
                $response
            );
        }

        return new self($message, $retryAfter, $error);
    }

    /**
     * Create an exception from a response array
     *
     * @param  array<string, mixed>  $response  The response data
     * @param  string|null  $message  Optional custom message
     */
    public static function fromResponse(array $response, ?string $message = null): self
    {
        $errorData = $response['error'] ?? $response;
        $defaultMessage = $errorData['message'] ?? 'Rate limit exceeded';

        $error = RateLimitErrorDTO::withRetry(
            0,
            $message ?? $defaultMessage,
            $response
        );

        return new self($message ?? $defaultMessage, 0, $error);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
