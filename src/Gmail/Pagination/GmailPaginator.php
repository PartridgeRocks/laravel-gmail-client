<?php

namespace PartridgeRocks\GmailClient\Gmail\Pagination;

use Illuminate\Support\Collection;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

/**
 * @template TValue
 */
class GmailPaginator
{
    use AcceptsJson;

    protected Connector $connector;

    protected ?string $nextPageToken = null;

    protected bool $hasMorePages = true;

    protected int $maxResults;

    protected string $resourceClass;

    protected ?string $responseKey = null;

    /** @var Collection<int, TValue> */
    protected Collection $items;

    /**
     * Create a new paginator instance.
     */
    public function __construct(
        Connector $connector,
        string $resourceClass,
        ?string $responseKey = null,
        int $maxResults = 100
    ) {
        $this->connector = $connector;
        $this->resourceClass = $resourceClass;
        $this->responseKey = $responseKey;
        $this->maxResults = $maxResults;
        $this->items = collect();
    }

    /**
     * Get the request for fetching the next page.
     */
    protected function getRequest(): mixed
    {
        $requestClass = new $this->resourceClass([
            'maxResults' => $this->maxResults,
            'pageToken' => $this->nextPageToken,
        ]);

        return $requestClass;
    }

    /**
     * Process the response to extract items and pagination token.
     */
    protected function processResponse(Response $response): void
    {
        $data = $response->json();

        // Store the next page token if it exists
        $this->nextPageToken = $data['nextPageToken'] ?? null;
        $this->hasMorePages = $this->nextPageToken !== null;

        // Extract items using the response key if provided
        $items = $data;
        if ($this->responseKey && isset($data[$this->responseKey])) {
            $items = $data[$this->responseKey];
        }

        // Store the items in the collection
        if (is_array($items)) {
            $this->items = $this->items->merge($items);
        }
    }

    /**
     * Get the next page of results.
     * @return Collection<int, TValue>
     */
    public function getNextPage(): Collection
    {
        if (! $this->hasMorePages) {
            return collect();
        }

        $request = $this->getRequest();
        $response = $this->connector->send($request);
        $this->processResponse($response);

        // Get the last N items where N is the max results
        return $this->items->slice(-$this->maxResults);
    }

    /**
     * Get all results by iterating through all pages.
     * @return Collection<int, TValue>
     */
    public function getAllPages(): Collection
    {
        while ($this->hasMorePages) {
            $this->getNextPage();
        }

        return $this->items;
    }

    /**
     * Transform collection using a DTO's static method.
     * @return Collection<int, TValue>
     */
    public function transformUsingDTO(string $dtoClass, string $method = 'collectionFromApiResponse'): Collection
    {
        $response = $this->getAllPages();

        // Create the response data structure expected by the DTO
        $dataKey = $this->responseKey ?? 'items';
        $responseData = [$dataKey => $response->toArray()];

        // Use the DTO's static method to transform the collection
        return $dtoClass::$method($responseData);
    }

    /**
     * Check if there are more pages available.
     */
    public function hasMorePages(): bool
    {
        return $this->hasMorePages;
    }

    /**
     * Get the current page token.
     */
    public function getPageToken(): ?string
    {
        return $this->nextPageToken;
    }
}
