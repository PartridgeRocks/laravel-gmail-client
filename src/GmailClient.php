<?php

namespace PartridgeRocks\GmailClient;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;
use PartridgeRocks\GmailClient\Services\AuthService;
use PartridgeRocks\GmailClient\Services\LabelService;
use PartridgeRocks\GmailClient\Services\MessageService;

class GmailClient
{
    use GmailClientHelpers;

    protected GmailConnector $connector;
    protected AuthService $authService;
    protected LabelService $labelService;
    protected MessageService $messageService;

    /**
     * Create a new GmailClient instance.
     *
     * @param  string|null  $accessToken  Optional access token to authenticate with
     */
    public function __construct(?string $accessToken = null)
    {
        $this->connector = new GmailConnector;
        $this->authService = new AuthService($this->connector);
        $this->labelService = new LabelService($this->connector);
        $this->messageService = new MessageService($this->connector);

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
     * Create a raw email message.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\ValidationException
     */
    protected function createEmailRaw(string $to, string $subject, string $body, array $options = []): string
    {
        $from = $options['from'] ?? config('gmail-client.from_email');

        if (empty($from)) {
            throw ValidationException::missingRequiredField('from_email');
        }

        // Validate sender email address
        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmailAddress($from);
        }

        $cc = $options['cc'] ?? null;
        $bcc = $options['bcc'] ?? null;

        $email = "From: {$from}\r\n";
        $email .= "To: {$to}\r\n";

        if ($cc) {
            $email .= "Cc: {$cc}\r\n";
        }

        if ($bcc) {
            $email .= "Bcc: {$bcc}\r\n";
        }

        $email .= "Subject: {$subject}\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/html; charset=utf-8\r\n";
        $email .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $email .= quoted_printable_encode($body);

        // Gmail API requires base64url encoding, not standard base64
        return $this->base64UrlEncode($email);
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
        $defaults = [
            'unread_limit' => config('gmail-client.performance.count_estimation_threshold', 25),
            'today_limit' => 15,
            'include_labels' => true,
            'estimate_large_counts' => config('gmail-client.performance.enable_smart_counting', true),
            'background_mode' => false,
            'timeout' => config('gmail-client.performance.api_timeout', 30),
        ];

        $config = array_merge($defaults, $options);
        $statistics = [
            'unread_count' => null,
            'today_count' => null,
            'labels_count' => null,
            'estimated_total' => null,
            'api_calls_made' => 0,
            'last_updated' => now()->toISOString(),
            'partial_failure' => false,
        ];

        try {
            // Get unread messages count with smart estimation
            $statistics = $this->getUnreadCount($statistics, $config);

            // Get today's messages count
            $statistics = $this->getTodayCount($statistics, $config);

            // Get labels count if requested
            if ($config['include_labels']) {
                $statistics = $this->getLabelsCount($statistics, $config);
            }

            // Get total mailbox estimation
            $statistics = $this->getMailboxEstimation($statistics, $config);

        } catch (\Exception $e) {
            $statistics['partial_failure'] = true;
            $statistics['error'] = $e->getMessage();

            // In background mode, don't throw exceptions
            if (! $config['background_mode']) {
                throw $e;
            }
        }

        return $statistics;
    }

    /**
     * Get unread messages count with smart estimation.
     */
    protected function getUnreadCount(array $statistics, array $config): array
    {
        try {
            $unreadQuery = ['q' => 'is:unread', 'maxResults' => $config['unread_limit']];
            $response = $this->messages()->list($unreadQuery);
            $data = $response->json();

            $statistics['api_calls_made']++;
            $messageCount = count($data['messages'] ?? []);

            // Smart estimation for large counts
            if ($config['estimate_large_counts'] && $messageCount >= $config['unread_limit']) {
                $statistics['unread_count'] = $config['unread_limit'].'+';
            } else {
                $statistics['unread_count'] = $messageCount;
            }

            // Store estimated total if available
            if (isset($data['resultSizeEstimate'])) {
                $statistics['unread_estimate'] = $data['resultSizeEstimate'];
            }

        } catch (\Exception $e) {
            $statistics['api_calls_made']++;
            $statistics['unread_count'] = '?';
            $statistics['partial_failure'] = true;
        }

        return $statistics;
    }

    /**
     * Get today's messages count.
     */
    protected function getTodayCount(array $statistics, array $config): array
    {
        try {
            $today = now()->format('Y/m/d');
            $todayQuery = ['q' => "after:{$today}", 'maxResults' => $config['today_limit']];
            $response = $this->messages()->list($todayQuery);
            $data = $response->json();

            $statistics['api_calls_made']++;
            $messageCount = count($data['messages'] ?? []);

            // Today's count is usually reasonable, so exact count unless very large
            if ($config['estimate_large_counts'] && $messageCount >= $config['today_limit']) {
                $statistics['today_count'] = $config['today_limit'].'+';
            } else {
                $statistics['today_count'] = $messageCount;
            }

        } catch (\Exception $e) {
            $statistics['api_calls_made']++;
            $statistics['today_count'] = '?';
            $statistics['partial_failure'] = true;
        }

        return $statistics;
    }

    /**
     * Get labels count.
     */
    protected function getLabelsCount(array $statistics, array $config): array
    {
        try {
            $response = $this->labels()->list();
            $data = $response->json();

            $statistics['api_calls_made']++;
            $statistics['labels_count'] = count($data['labels'] ?? []);

        } catch (\Exception $e) {
            $statistics['api_calls_made']++;
            $statistics['labels_count'] = '?';
            $statistics['partial_failure'] = true;
        }

        return $statistics;
    }

    /**
     * Get mailbox size estimation.
     */
    protected function getMailboxEstimation(array $statistics, array $config): array
    {
        try {
            // Use a broad query to get total mailbox estimation
            $response = $this->messages()->list(['q' => 'in:anywhere', 'maxResults' => 1]);
            $data = $response->json();

            $statistics['api_calls_made']++;

            if (isset($data['resultSizeEstimate'])) {
                $statistics['estimated_total'] = $data['resultSizeEstimate'];
            }

        } catch (\Exception $e) {
            $statistics['api_calls_made']++;
            $statistics['estimated_total'] = null;
            // Don't mark as partial failure for this optional metric
        }

        return $statistics;
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
        $health = [
            'connected' => false,
            'token_expires_in' => null,
            'api_quota_remaining' => null,
            'last_successful_call' => null,
            'errors' => [],
            'status' => 'unknown',
        ];

        try {
            // Test connection with a minimal API call
            $response = $this->labels()->list();

            if ($response->successful()) {
                $health['connected'] = true;
                $health['last_successful_call'] = now()->toISOString();
                $health['status'] = 'healthy';

                // Check for rate limit headers if available
                $remaining = $response->header('X-RateLimit-Remaining');
                if ($remaining !== null) {
                    $health['api_quota_remaining'] = (int) $remaining;
                }
            } else {
                $health['status'] = 'unhealthy';
                $health['errors'][] = "API call failed with status: {$response->status()}";
            }

        } catch (AuthenticationException $e) {
            $health['status'] = 'authentication_failed';
            $health['errors'][] = $e->getMessage();
        } catch (RateLimitException $e) {
            $health['status'] = 'rate_limited';
            $health['api_quota_remaining'] = 0;
            $health['errors'][] = $e->getMessage();
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['errors'][] = $e->getMessage();
        }

        // Note: Token expiration checking would require extending the authenticator
        // This is a future enhancement opportunity

        return $health;
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
                    return collect();
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
                    return collect();
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
        try {
            return $this->getAccountStatistics(array_merge(['background_mode' => true], $options));
        } catch (\Exception $e) {
            logger()->warning('Failed to get Gmail account statistics: '.$e->getMessage());

            return [
                'unread_count' => '?',
                'today_count' => '?',
                'labels_count' => '?',
                'estimated_total' => null,
                'api_calls_made' => 0,
                'last_updated' => now()->toISOString(),
                'partial_failure' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the client is properly authenticated and connected.
     */
    public function isConnected(): bool
    {
        try {
            $health = $this->getAccountHealth();

            return $health['connected'] && $health['status'] === 'healthy';
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get a summary of Gmail account status with safe fallbacks.
     */
    public function getAccountSummary(): array
    {
        $summary = [
            'connected' => false,
            'labels_count' => 0,
            'has_unread' => false,
            'errors' => [],
        ];

        try {
            // Check connection
            $summary['connected'] = $this->isConnected();

            if ($summary['connected']) {
                // Get safe statistics
                $stats = $this->safeGetAccountStatistics([
                    'unread_limit' => 1, // Just check if any unread exist
                    'include_labels' => true,
                ]);

                $summary['labels_count'] = is_numeric($stats['labels_count']) ? $stats['labels_count'] : 0;
                $summary['has_unread'] = ! in_array($stats['unread_count'], ['?', 0, '0']);

                if ($stats['partial_failure']) {
                    $summary['errors'][] = 'STATISTICS_UNAVAILABLE';
                }
            }
        } catch (\Exception $e) {
            logger()->warning("Gmail operation failed: get account summary - {$e->getMessage()}", [
                'operation' => 'get account summary',
                'error_type' => get_class($e),
            ]);
            $summary['errors'][] = 'ACCOUNT_SUMMARY_ERROR';
        }

        return $summary;
    }
}
