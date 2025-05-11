<?php

namespace PartridgeRocks\GmailClient\Exceptions;

class RateLimitException extends GmailClientException
{
    protected int $retryAfter;

    public function __construct(string $message, int $retryAfter = 0)
    {
        parent::__construct($message);
        $this->retryAfter = $retryAfter;
    }

    public static function quotaExceeded(int $retryAfter = 0): self
    {
        return new static(
            'You have exceeded the Gmail API rate limit. Please try again later.',
            $retryAfter
        );
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
