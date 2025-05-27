<?php

namespace PartridgeRocks\GmailClient\Services;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Constants\ConfigDefaults;
use PartridgeRocks\GmailClient\Constants\GmailConstants;
use PartridgeRocks\GmailClient\Constants\HttpStatus;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\ListLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;

class LabelService implements LabelServiceInterface
{
    use GmailClientHelpers;

    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * List labels with various options.
     */
    public function listLabels(bool $paginate = false, bool $lazy = false, int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS): mixed
    {
        if ($lazy) {
            return $this->lazyLoadLabels();
        }

        if ($paginate) {
            return $this->paginateLabels($maxResults);
        }

        $response = $this->getLabelResource()->list();
        $data = $response->json();

        return collect($data['labels'] ?? [])->map(function ($label) {
            return Label::fromApiResponse($label);
        });
    }

    /**
     * Create a paginator for labels.
     */
    public function paginateLabels(int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS): GmailPaginator
    {
        return new GmailPaginator(
            $this->connector,
            ListLabelsRequest::class,
            'labels',
            $maxResults
        );
    }

    /**
     * Create a lazy-loading collection for labels.
     * Note: Lazy loading should be handled by GmailClient directly.
     */
    public function lazyLoadLabels(): \Illuminate\Support\LazyCollection
    {
        // Return empty lazy collection since lazy loading requires GmailClient instance
        return collect()->lazy();
    }

    /**
     * Get a specific label.
     *
     * @throws NotFoundException
     * @throws AuthenticationException
     * @throws RateLimitException
     */
    public function getLabel(string $id): Label
    {
        $response = $this->getLabelResource()->get($id);

        if ($response->status() === HttpStatus::NOT_FOUND) {
            throw NotFoundException::label($id);
        }

        if ($response->status() === HttpStatus::UNAUTHORIZED) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === HttpStatus::TOO_MANY_REQUESTS) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');

            throw RateLimitException::quotaExceeded($retryAfter);
        }

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Create a new label.
     */
    public function createLabel(string $name, array $options = []): Label
    {
        $labelData = [
            'name' => $name,
            'messageListVisibility' => $options['messageListVisibility'] ?? GmailConstants::VISIBILITY_SHOW,
            'labelListVisibility' => $options['labelListVisibility'] ?? GmailConstants::LABEL_LIST_VISIBILITY_SHOW,
        ];

        // Add optional properties
        if (isset($options['backgroundColor'])) {
            $labelData['color']['backgroundColor'] = $options['backgroundColor'];
        }

        if (isset($options['textColor'])) {
            $labelData['color']['textColor'] = $options['textColor'];
        }

        $response = $this->getLabelResource()->create($labelData);

        if ($response->status() === HttpStatus::BAD_REQUEST) {
            throw new \PartridgeRocks\GmailClient\Exceptions\ValidationException('Invalid label data provided');
        }

        if ($response->status() === HttpStatus::UNAUTHORIZED) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === HttpStatus::TOO_MANY_REQUESTS) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');

            throw RateLimitException::quotaExceeded($retryAfter);
        }

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Update an existing label.
     */
    public function updateLabel(string $id, array $updates): Label
    {
        $response = $this->getLabelResource()->update($id, $updates);

        if ($response->status() === HttpStatus::NOT_FOUND) {
            throw NotFoundException::label($id);
        }

        if ($response->status() === HttpStatus::BAD_REQUEST) {
            throw new \PartridgeRocks\GmailClient\Exceptions\ValidationException('Invalid label data provided');
        }

        if ($response->status() === HttpStatus::UNAUTHORIZED) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === HttpStatus::TOO_MANY_REQUESTS) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');

            throw RateLimitException::quotaExceeded($retryAfter);
        }

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Delete a label.
     */
    public function deleteLabel(string $id): bool
    {
        $response = $this->getLabelResource()->delete($id);

        if ($response->status() === HttpStatus::NOT_FOUND) {
            throw NotFoundException::label($id);
        }

        if ($response->status() === HttpStatus::UNAUTHORIZED) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === HttpStatus::TOO_MANY_REQUESTS) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');

            throw RateLimitException::quotaExceeded($retryAfter);
        }

        return $response->successful();
    }

    /**
     * Safely list labels, returning empty collection on failure.
     */
    public function safeListLabels(bool $paginate = false, bool $lazy = false, int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS): mixed
    {
        return $this->safeCall(
            callback: fn () => $this->listLabels($paginate, $lazy, $maxResults),
            fallback: $this->getEmptyLabelsStructure($paginate, $lazy, $maxResults),
            operation: 'list labels',
            context: ['paginate' => $paginate, 'lazy' => $lazy, 'maxResults' => $maxResults]
        );
    }

    /**
     * Execute a callable safely with error handling and logging.
     */
    private function safeCall(callable $callback, mixed $fallback, string $operation, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            logger()->warning("Gmail operation failed: {$operation} - {$e->getMessage()}", array_merge([
                'operation' => $operation,
                'error_type' => get_class($e),
            ], $context));

            return $fallback;
        }
    }

    /**
     * Get appropriate empty structure for labels based on requested format.
     */
    private function getEmptyLabelsStructure(bool $paginate, bool $lazy, int $maxResults): mixed
    {
        if ($paginate) {
            return $this->paginateLabels($maxResults);
        }

        if ($lazy) {
            return $this->lazyLoadLabels();
        }

        return collect();
    }

    /**
     * Parse the Retry-After header value.
     */
    private function parseRetryAfterHeader(string $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return max(0, $timestamp - time());
        }

        return ConfigDefaults::DEFAULT_RETRY_AFTER_SECONDS;
    }

    /**
     * Get the label resource.
     */
    private function getLabelResource(): LabelResource
    {
        return new LabelResource($this->connector);
    }
}
