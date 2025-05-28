<?php

namespace PartridgeRocks\GmailClient\Contracts;

use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;

interface StatisticsServiceInterface
{
    /**
     * Get comprehensive account statistics with optimized batch retrieval.
     *
     * @param  array<string, mixed>  $options  Configuration options for statistics retrieval
     * @return array<string, mixed> Comprehensive account statistics
     *
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function getAccountStatistics(array $options = []): array;

    /**
     * Get account health status with connection and API quota information.
     *
     * @return array<string, mixed> Health status with connection, authentication, and quota details
     */
    public function getAccountHealth(): array;

    /**
     * Safely get account statistics with graceful fallback.
     *
     * @param  array<string, mixed>  $options  Configuration options for statistics retrieval
     * @return array<string, mixed> Statistics with partial_failure flag if needed
     */
    public function safeGetAccountStatistics(array $options = []): array;

    /**
     * Check if the Gmail API connection is healthy and authenticated.
     *
     * @return bool True if connected and authenticated, false otherwise
     */
    public function isConnected(): bool;

    /**
     * Get a high-level account summary with safe fallbacks.
     *
     * @return array<string, mixed> Account summary with connection status and basic metrics
     */
    public function getAccountSummary(): array;
}
