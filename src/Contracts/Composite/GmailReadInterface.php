<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;

/**
 * Composite interface for read-only Gmail operations.
 *
 * This interface combines all read operations (messages, labels, statistics)
 * into a single contract, enabling better dependency injection and cleaner
 * architecture for components that only need read access.
 */
interface GmailReadInterface
{
    /**
     * List messages with optional filtering.
     *
     * @param  array<string, mixed>  $query
     * @return Collection<int, Email>|GmailPaginator<Email>|LazyCollection<int, Email>
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed;

    /**
     * Get a single message by ID.
     */
    public function getMessage(string $id): Email;

    /**
     * List all labels.
     *
     * @return Collection<int, Label>
     */
    public function listLabels(bool $paginate = false, bool $lazy = false): mixed;

    /**
     * Get a single label by ID.
     */
    public function getLabel(string $id): Label;

    /**
     * Get account statistics.
     *
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getAccountStatistics(array $options = []): array;

    /**
     * Get account health status.
     *
     * @return array<string, mixed>
     */
    public function getAccountHealth(): array;

    /**
     * Check if Gmail connection is healthy.
     */
    public function isConnected(): bool;

    /**
     * Get account summary.
     *
     * @return array<string, mixed>
     */
    public function getAccountSummary(): array;
}
