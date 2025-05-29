<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;

/**
 * Composite interface for safe/error-tolerant Gmail operations.
 *
 * This interface provides methods that gracefully handle failures and
 * return safe defaults instead of throwing exceptions. Ideal for
 * dashboard widgets and background processing.
 */
interface GmailSafeInterface
{
    /**
     * Safely list messages - returns empty collection on failure.
     *
     * @param  array<string, mixed>  $query
     * @return Collection<int, Email>|LazyCollection<int, Email>
     */
    public function safeListMessages(
        array $query = [],
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): Collection|LazyCollection;

    /**
     * Safely get a message - returns null on failure.
     */
    public function safeGetMessage(string $id): ?Email;

    /**
     * Safely list labels - returns empty collection on failure.
     *
     * @return Collection<int, Label>|LazyCollection<int, Label>
     */
    public function safeListLabels(bool $lazy = false, bool $paginate = false): Collection|LazyCollection;

    /**
     * Safely get account statistics - returns fallback data on failure.
     *
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function safeGetAccountStatistics(array $options = []): array;

    /**
     * Check connection health - never throws exceptions.
     */
    public function isConnected(): bool;

    /**
     * Get account summary with safe fallbacks.
     *
     * @return array<string, mixed>
     */
    public function getAccountSummary(): array;
}
