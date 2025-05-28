<?php

namespace PartridgeRocks\GmailClient\Contracts;

use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;

interface LabelServiceInterface
{
    /**
     * List labels with various options.
     */
    public function listLabels(bool $paginate = false, bool $lazy = false, ?int $maxResults = null): mixed;

    /**
     * Create a paginator for labels.
     *
     * @return GmailPaginator<Label>
     */
    public function paginateLabels(?int $maxResults = null): GmailPaginator;

    /**
     * Create a lazy-loading collection for labels.
     *
     * @return LazyCollection<int, Label>
     */
    public function lazyLoadLabels(): LazyCollection;

    /**
     * Get a specific label.
     *
     * @throws NotFoundException
     * @throws AuthenticationException
     * @throws RateLimitException
     */
    public function getLabel(string $id): Label;

    /**
     * Create a new label.
     *
     * @param  array<string, mixed>  $options
     */
    public function createLabel(string $name, array $options = []): Label;

    /**
     * Update an existing label.
     *
     * @param  array<string, mixed>  $updates
     */
    public function updateLabel(string $id, array $updates): Label;

    /**
     * Delete a label.
     */
    public function deleteLabel(string $id): bool;

    /**
     * Safely list labels, returning empty collection on failure.
     */
    public function safeListLabels(bool $paginate = false, bool $lazy = false, ?int $maxResults = null): mixed;
}
