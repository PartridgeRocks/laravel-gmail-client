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
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed;

    /**
     * Create a paginator for messages.
     */
    public function paginateMessages(array $query = [], int $maxResults = 100): GmailPaginator;

    /**
     * Create a lazy-loading collection for messages.
     */
    public function lazyLoadMessages(array $query = [], int $maxResults = 100, bool $fullDetails = true): LazyCollection;

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
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email;

    /**
     * Add labels to a message.
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email;

    /**
     * Remove labels from a message.
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email;

    /**
     * Modify message labels (add and/or remove).
     */
    public function modifyMessageLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Email;

    /**
     * Safely get a message, returning null on failure.
     */
    public function safeGetMessage(string $id): ?Email;

    /**
     * Safely list messages, returning empty collection on failure.
     */
    public function safeListMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed;
}
