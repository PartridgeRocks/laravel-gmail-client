<?php

namespace PartridgeRocks\GmailClient\Services;

use PartridgeRocks\GmailClient\Contracts\StatisticsServiceInterface;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class StatisticsService implements StatisticsServiceInterface
{
    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * Get comprehensive account statistics with optimized batch retrieval.
     *
     * @param  array  $options  Configuration options for statistics retrieval
     * @return array Comprehensive account statistics
     *
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
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
            // Get unread count with smart estimation
            $statistics['unread_count'] = $this->getUnreadCount($config);
            $statistics['api_calls_made']++;

            // Get today's count if not in background mode
            if (! $config['background_mode']) {
                $statistics['today_count'] = $this->getTodayCount($config);
                $statistics['api_calls_made']++;
            }

            // Get labels count if enabled
            if ($config['include_labels']) {
                $statistics['labels_count'] = $this->getLabelsCount();
                $statistics['api_calls_made']++;
            }

            // Get mailbox size estimation for large accounts
            if ($config['estimate_large_counts']) {
                $statistics['estimated_total'] = $this->getMailboxEstimation($config);
                if ($statistics['estimated_total'] !== null) {
                    $statistics['api_calls_made']++;
                }
            }
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
     * Get account health status with connection and API quota information.
     *
     * @return array Health status with connection, authentication, and quota details
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
            $response = $this->getLabelResource()->list();

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
                'api_calls_made' => 1,
                'last_updated' => now()->toISOString(),
                'partial_failure' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the Gmail API connection is healthy and authenticated.
     *
     * @return bool True if connected and authenticated, false otherwise
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
     * Get a high-level account summary with safe fallbacks.
     *
     * @return array Account summary with connection status and basic metrics
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

    /**
     * Get unread message count with smart estimation for large volumes.
     */
    protected function getUnreadCount(array $config): int
    {
        try {
            $response = $this->getMessageResource()->list(['q' => 'is:unread', 'maxResults' => $config['unread_limit']]);
            $data = $response->json();

            $actualCount = count($data['messages'] ?? []);

            // If we hit the limit and smart estimation is enabled, estimate total
            if ($actualCount >= $config['unread_limit'] && $config['estimate_large_counts']) {
                // Use a simple estimation: if we got max results, assume there are more
                return $actualCount * 2; // Conservative estimate
            }

            return $actualCount;
        } catch (\Exception $e) {
            logger()->debug('Unread count failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get today's message count.
     */
    protected function getTodayCount(array $config): int
    {
        try {
            $today = now()->format('Y/m/d');
            $response = $this->getMessageResource()->list([
                'q' => "after:{$today}",
                'maxResults' => $config['today_limit'],
            ]);

            $data = $response->json();

            return count($data['messages'] ?? []);
        } catch (\Exception $e) {
            logger()->debug('Today count failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get total labels count.
     */
    protected function getLabelsCount(): int
    {
        try {
            $response = $this->getLabelResource()->list();
            $data = $response->json();

            return count($data['labels'] ?? []);
        } catch (\Exception $e) {
            logger()->debug('Labels count failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get mailbox size estimation for large accounts.
     */
    protected function getMailboxEstimation(array $config): ?int
    {
        try {
            // Get a small sample to estimate total size
            $response = $this->getMessageResource()->list(['maxResults' => 10]);
            $data = $response->json();

            // If there's a resultSizeEstimate, use it
            if (isset($data['resultSizeEstimate'])) {
                return (int) $data['resultSizeEstimate'];
            }

            // Otherwise return null to indicate estimation unavailable
            return null;
        } catch (\Exception $e) {
            logger()->debug('Mailbox estimation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get the message resource.
     */
    private function getMessageResource(): MessageResource
    {
        return new MessageResource($this->connector);
    }

    /**
     * Get the label resource.
     */
    private function getLabelResource(): LabelResource
    {
        return new LabelResource($this->connector);
    }
}
