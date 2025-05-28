<?php

namespace PartridgeRocks\GmailClient\Contracts;

use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;

interface MessageServiceInterface
{
    /**
     * List messages with various options.
     *
     * @param  array<string, mixed>  $query
     * @param  int|null  $maxResults
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed;

    /**
     * Create a paginator for messages.
     *
     * @param  array<string, mixed>  $query
     * @param  int|null  $maxResults
     * @return GmailPaginator<Email>
     */
    public function paginateMessages(array $query = [], ?int $maxResults = null): GmailPaginator;

    /**
     * Create a lazy-loading collection for messages.
     *
     * @param  array<string, mixed>  $query
     * @param  int|null  $maxResults
     * @return LazyCollection<int, Email>
     */
    public function lazyLoadMessages(array $query = [], ?int $maxResults = null, bool $fullDetails = true): LazyCollection;

    /**
     * Get a specific message.
     *
     * @throws NotFoundException
     * @throws AuthenticationException
     * @throws RateLimitException
     */
    public function getMessage(string $id): Email;

    /**
     * Send an email.
     *
     * @param  array<string, mixed>  $options
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email;

    /**
     * Add labels to a message.
     *
     * @param  array<string>  $labelIds
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email;

    /**
     * Remove labels from a message.
     *
     * @param  array<string>  $labelIds
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email;

    /**
     * Modify message labels (add and/or remove).
     *
     * @param  array<string>  $addLabelIds
     * @param  array<string>  $removeLabelIds
     */
    public function modifyMessageLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Email;

    /**
     * Safely get a message, returning null on failure.
     */
    public function safeGetMessage(string $id): ?Email;

    /**
     * Safely list messages, returning empty collection on failure.
     *
     * @param  array<string, mixed>  $query
     * @param  int|null  $maxResults
     */
    public function safeListMessages(
        array $query = [],
        bool $paginate = false,
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed;
}
