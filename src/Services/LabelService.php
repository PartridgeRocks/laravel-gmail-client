<?php

namespace PartridgeRocks\GmailClient\Services;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\Gmail\ExceptionHandling;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\ListLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;

class LabelService implements LabelServiceInterface
{
    use ExceptionHandling;
    use GmailClientHelpers;

    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * List labels with various options.
     *
     * @param  bool  $paginate  Whether to return a paginator instance
     * @param  bool  $lazy  Whether to return a lazy collection
     * @param  int  $maxResults  Maximum number of results per page
     * @return mixed Collection, Paginator, or LazyCollection based on parameters
     *
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function listLabels(bool $paginate = false, bool $lazy = false, ?int $maxResults = null): mixed
    {
        $maxResults = $maxResults ?? config('gmail-client.pagination.default_page_size', 100);

        if ($lazy) {
            return $this->lazyLoadLabels();
        }

        if ($paginate) {
            return $this->paginateLabels($maxResults);
        }

        $response = $this->getLabelResource()->list();
        $data = $response->json();

        /** @var array<int, array<string, mixed>> $labels */
        $labels = $data['labels'] ?? [];

        return collect($labels)->map(function (array $label) {
            return Label::fromApiResponse($label);
        });
    }

    /**
     * Create a paginator for labels.
     *
     * @return GmailPaginator<Label>
     */
    public function paginateLabels(?int $maxResults = null): GmailPaginator
    {
        $maxResults = $maxResults ?? config('gmail-client.pagination.default_page_size', 100);

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
     * @param  string  $id  The label ID to retrieve
     * @return Label The label data
     *
     * @throws NotFoundException When the label is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function getLabel(string $id): Label
    {
        $response = $this->getLabelResource()->get($id);

        $this->handleApiResponse($response, 'label', $id);

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Create a new label.
     *
     * @param  string  $name  The label name
     * @param  array<string, mixed>  $options  Optional settings:
     *                                         - messageListVisibility: string Visibility in message list
     *                                         - labelListVisibility: string Visibility in label list
     *                                         - backgroundColor: string Background color hex code
     *                                         - textColor: string Text color hex code
     * @return Label The created label data
     *
     * @throws ValidationException When label data is invalid
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function createLabel(string $name, array $options = []): Label
    {
        $labelData = [
            'name' => $name,
            'messageListVisibility' => $options['messageListVisibility'] ?? config('gmail-client.labels.visibility.show'),
            'labelListVisibility' => $options['labelListVisibility'] ?? config('gmail-client.labels.visibility.label_show'),
        ];

        // Add optional properties
        if (isset($options['backgroundColor'])) {
            $labelData['color']['backgroundColor'] = $options['backgroundColor'];
        }

        if (isset($options['textColor'])) {
            $labelData['color']['textColor'] = $options['textColor'];
        }

        $response = $this->getLabelResource()->create($labelData);

        $this->handleApiResponse($response, 'label');

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Update an existing label.
     *
     * @param  string  $id  The label ID to update
     * @param  array<string, mixed>  $updates  Array of label properties to update
     * @return Label The updated label data
     *
     * @throws NotFoundException When the label is not found
     * @throws ValidationException When update data is invalid
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function updateLabel(string $id, array $updates): Label
    {
        $response = $this->getLabelResource()->update($id, $updates);

        $this->handleApiResponse($response, 'label', $id);

        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Delete a label.
     *
     * @param  string  $id  The label ID to delete
     * @return bool True if deletion was successful
     *
     * @throws NotFoundException When the label is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function deleteLabel(string $id): bool
    {
        $response = $this->getLabelResource()->delete($id);

        $this->handleApiResponse($response, 'label', $id);

        return $response->successful();
    }

    /**
     * Safely list labels, returning empty collection on failure.
     *
     * @param  bool  $paginate  Whether to return a paginator instance
     * @param  bool  $lazy  Whether to return a lazy collection
     * @param  int  $maxResults  Maximum number of results per page
     * @return mixed Collection, Paginator, or LazyCollection based on parameters
     */
    public function safeListLabels(bool $paginate = false, bool $lazy = false, ?int $maxResults = null): mixed
    {
        $maxResults = $maxResults ?? config('gmail-client.pagination.default_page_size', 100);

        return $this->safeCall(
            callback: fn () => $this->listLabels($paginate, $lazy, $maxResults),
            fallback: $this->getEmptyLabelsStructure($paginate, $lazy, $maxResults),
            operation: 'list labels',
            context: ['paginate' => $paginate, 'lazy' => $lazy, 'maxResults' => $maxResults]
        );
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
     *
     * @param  string  $value  The Retry-After header value
     * @return int Number of seconds to wait before retrying
     */
    protected function parseRetryAfterHeader(string $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return max(0, $timestamp - time());
        }

        return config('gmail-client.api.default_retry_after_seconds', 60);
    }

    /**
     * Get the label resource.
     */
    private function getLabelResource(): LabelResource
    {
        return new LabelResource($this->connector);
    }
}
