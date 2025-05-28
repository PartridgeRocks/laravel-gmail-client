<?php

namespace PartridgeRocks\GmailClient;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;
use PartridgeRocks\GmailClient\Contracts\StatisticsServiceInterface;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;
use PartridgeRocks\GmailClient\Services\AuthService;
use PartridgeRocks\GmailClient\Services\LabelService;
use PartridgeRocks\GmailClient\Services\MessageService;
use PartridgeRocks\GmailClient\Services\StatisticsService;

/**
 * Gmail Client - Main interface for Gmail API operations.
 *
 * This is the primary entry point for interacting with the Gmail API. It provides
 * a unified interface for authentication, message management, label operations,
 * and various Gmail-specific utilities. The client uses dependency injection
 * for flexibility and testability.
 *
 * Key Features:
 * - OAuth2 authentication with automatic token management
 * - Complete message CRUD operations (list, get, send, modify)
 * - Label management (create, update, delete, organize)
 * - Safe operation methods with graceful error handling
 * - Pagination support for large datasets
 * - CRM-friendly contact parsing and domain filtering
 * - Account statistics and health monitoring
 *
 * Usage Examples:
 * ```php
 * // Basic usage
 * $client = new GmailClient('your-access-token');
 * $messages = $client->listMessages(['q' => 'is:unread']);
 *
 * // OAuth flow
 * $client = new GmailClient();
 * $authUrl = $client->getAuthorizationUrl('redirect-uri', ['gmail.readonly']);
 * $tokens = $client->exchangeCode('auth-code', 'redirect-uri');
 *
 * // Safe operations (won't throw exceptions)
 * $messages = $client->safeListMessages();
 * $isConnected = $client->isConnected();
 * ```
 *
 * @see https://developers.google.com/gmail/api
 */
class GmailClient
{
    protected GmailConnector $connector;
    protected AuthServiceInterface $authService;
    protected LabelServiceInterface $labelService;
    protected MessageServiceInterface $messageService;
    protected StatisticsServiceInterface $statisticsService;

    /**
     * Create a new GmailClient instance.
     *
     * @param  string|null  $accessToken  Optional access token to authenticate with
     * @param  AuthServiceInterface|null  $authService  Optional auth service implementation
     * @param  LabelServiceInterface|null  $labelService  Optional label service implementation
     * @param  MessageServiceInterface|null  $messageService  Optional message service implementation
     * @param  StatisticsServiceInterface|null  $statisticsService  Optional statistics service implementation
     * @param  GmailConnector|null  $connector  Optional Gmail connector implementation
     */
    public function __construct(
        ?string $accessToken = null,
        ?AuthServiceInterface $authService = null,
        ?LabelServiceInterface $labelService = null,
        ?MessageServiceInterface $messageService = null,
        ?StatisticsServiceInterface $statisticsService = null,
        ?GmailConnector $connector = null
    ) {
        $this->connector = $connector ?? new GmailConnector;
        $this->authService = $authService ?? new AuthService($this->connector);
        $this->labelService = $labelService ?? new LabelService($this->connector);
        $this->messageService = $messageService ?? new MessageService($this->connector);
        $this->statisticsService = $statisticsService ?? new StatisticsService($this->connector);

        if ($accessToken) {
            $this->authenticate($accessToken);
        }
    }

    /**
     * Get the connector instance.
     */
    public function getConnector(): GmailConnector
    {
        return $this->connector;
    }

    /**
     * Authenticate with a token.
     *
     * @return $this
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeInterface $expiresAt = null
    ): self {
        $this->authService->authenticate($accessToken, $refreshToken, $expiresAt);

        return $this;
    }

    /**
     * Get the authorization URL for the OAuth flow.
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        return $this->authService->getAuthorizationUrl($redirectUri, $scopes, $additionalParams);
    }

    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        return $this->authService->exchangeCode($code, $redirectUri);
    }

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->authService->refreshToken($refreshToken);
    }

    /**
     * Get the message resource.
     */
    public function messages(): MessageResource
    {
        return new MessageResource($this->connector);
    }

    /**
     * Get the label resource.
     */
    public function labels(): LabelResource
    {
        return new LabelResource($this->connector);
    }

    /**
     * List messages with optional query parameters.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  bool  $fullDetails  Whether to fetch full message details (only applies with lazy=true)
     * @return Collection|GmailPaginator|\Illuminate\Support\LazyCollection
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        if ($lazy) {
            return $this->lazyLoadMessages($query, $maxResults, $fullDetails);
        }

        return $this->messageService->listMessages($query, $paginate, $maxResults, false, $fullDetails);
    }

    /**
     * Create a paginator for messages.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  int  $maxResults  Maximum number of results per page
     */
    public function paginateMessages(array $query = [], int $maxResults = 100): GmailPaginator
    {
        return $this->messageService->paginateMessages($query, $maxResults);
    }

    /**
     * Create a lazy-loading collection for messages.
     * This provides memory-efficient iteration over messages.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $fullDetails  Whether to fetch full message details or just basic info
     */
    public function lazyLoadMessages(array $query = [], int $maxResults = 100, bool $fullDetails = true): Gmail\Pagination\GmailLazyCollection
    {
        return Gmail\Pagination\GmailLazyCollection::messages($this, $query, $maxResults, $fullDetails);
    }

    /**
     * Create a lazy-loading collection for labels.
     * This provides memory-efficient iteration over labels.
     */
    public function lazyLoadLabels(): Gmail\Pagination\GmailLazyCollection
    {
        return Gmail\Pagination\GmailLazyCollection::labels($this);
    }

    /**
     * Get a specific message.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\NotFoundException
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function getMessage(string $id): Email
    {
        return $this->messageService->getMessage($id);
    }

    /**
     * Send a new email message.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\ValidationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email
    {
        return $this->messageService->sendEmail($to, $subject, $body, $options);
    }

    /**
     * List all labels.
     *
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  int  $maxResults  Maximum number of results per page
     * @return Collection|GmailPaginator|Gmail\Pagination\GmailLazyCollection
     */
    public function listLabels(bool $paginate = false, bool $lazy = false, int $maxResults = 100): mixed
    {
        if ($lazy) {
            return $this->lazyLoadLabels();
        }

        return $this->labelService->listLabels($paginate, false, $maxResults);
    }

    /**
     * Create a paginator for labels.
     *
     * @param  int  $maxResults  Maximum number of results per page
     */
    public function paginateLabels(int $maxResults = 100): GmailPaginator
    {
        return $this->labelService->paginateLabels($maxResults);
    }

    /**
     * Get a specific label.
     */
    public function getLabel(string $id): Label
    {
        return $this->labelService->getLabel($id);
    }

    /**
     * Create a new label.
     */
    public function createLabel(string $name, array $options = []): Label
    {
        return $this->labelService->createLabel($name, $options);
    }

    /**
     * Update an existing label.
     */
    public function updateLabel(string $id, array $updates): Label
    {
        return $this->labelService->updateLabel($id, $updates);
    }

    /**
     * Delete a label.
     */
    public function deleteLabel(string $id): bool
    {
        return $this->labelService->deleteLabel($id);
    }

    /**
     * Add labels to a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $labelIds  Array of label IDs to add
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\NotFoundException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email
    {
        return $this->messageService->addLabelsToMessage($messageId, $labelIds);
    }

    /**
     * Remove labels from a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $labelIds  Array of label IDs to remove
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\NotFoundException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email
    {
        return $this->messageService->removeLabelsFromMessage($messageId, $labelIds);
    }

    /**
     * Modify labels on a message (add and/or remove labels).
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $addLabelIds  Array of label IDs to add (optional)
     * @param  array  $removeLabelIds  Array of label IDs to remove (optional)
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\NotFoundException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function modifyMessageLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Email
    {
        return $this->messageService->modifyMessageLabels($messageId, $addLabelIds, $removeLabelIds);
    }

    /**
     * Get account statistics with optimized batch retrieval.
     *
     * @param  array  $options  Configuration options for statistics retrieval
     * @return array Comprehensive account statistics
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function getAccountStatistics(array $options = []): array
    {
        return $this->statisticsService->getAccountStatistics($options);
    }

    /**
     * Get account health information.
     *
     * @return array Health status including connection, token, and API quota info
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function getAccountHealth(): array
    {
        return $this->statisticsService->getAccountHealth();
    }

    /**
     * Safely attempt to list labels, returning empty collection on failure.
     *
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  int  $maxResults  Maximum number of results per page
     * @return \Illuminate\Support\Collection|GmailPaginator|Gmail\Pagination\GmailLazyCollection|\Illuminate\Support\LazyCollection
     */
    public function safeListLabels(bool $paginate = false, bool $lazy = false, int $maxResults = 100): mixed
    {
        if ($lazy) {
            try {
                return $this->lazyLoadLabels();
            } catch (\Exception $e) {
                logger()->warning("Gmail operation failed: list labels (lazy) - {$e->getMessage()}", [
                    'operation' => 'list labels',
                    'error_type' => get_class($e),
                    'lazy' => true,
                ]);

                // Return empty GmailLazyCollection for consistency
                return new Gmail\Pagination\GmailLazyCollection(function () {
                    yield from [];
                });
            }
        }

        return $this->labelService->safeListLabels($paginate, false, $maxResults);
    }

    /**
     * Safely attempt to list messages, returning empty collection on failure.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  bool  $fullDetails  Whether to fetch full message details (only applies with lazy=true)
     * @return \Illuminate\Support\Collection|GmailPaginator|\Illuminate\Support\LazyCollection
     */
    public function safeListMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        if ($lazy) {
            try {
                return $this->lazyLoadMessages($query, $maxResults, $fullDetails);
            } catch (\Exception $e) {
                logger()->warning("Gmail operation failed: list messages (lazy) - {$e->getMessage()}", [
                    'operation' => 'list messages',
                    'error_type' => get_class($e),
                    'query' => $query,
                    'lazy' => true,
                ]);

                // Return empty GmailLazyCollection for consistency
                return new Gmail\Pagination\GmailLazyCollection(function () {
                    yield from [];
                });
            }
        }

        return $this->messageService->safeListMessages($query, $paginate, $maxResults, false, $fullDetails);
    }

    /**
     * Safely get a message, returning null on failure.
     *
     * @param  string  $id  Message ID
     */
    public function safeGetMessage(string $id): ?Email
    {
        return $this->messageService->safeGetMessage($id);
    }

    /**
     * Safely get account statistics with graceful fallback.
     *
     * @param  array  $options  Configuration options for statistics retrieval
     * @return array Statistics with partial_failure flag if needed
     */
    public function safeGetAccountStatistics(array $options = []): array
    {
        return $this->statisticsService->safeGetAccountStatistics($options);
    }

    /**
     * Check if the client is properly authenticated and connected.
     */
    public function isConnected(): bool
    {
        return $this->statisticsService->isConnected();
    }

    /**
     * Get a summary of Gmail account status with safe fallbacks.
     */
    public function getAccountSummary(): array
    {
        return $this->statisticsService->getAccountSummary();
    }
}
