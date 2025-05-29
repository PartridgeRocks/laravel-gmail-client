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
     * Get the next page of results.
     *
     * @return Collection<int, TValue>
     */
    public function getNextPage(): Collection
    {
        if (! $this->hasMorePages) {
            return collect();
        }

        $request = $this->getRequest();
        $response = $this->connector->send($request);
        $data = $response->json();

        // Update pagination state
        $this->nextPageToken = $data['nextPageToken'] ?? null;
        $this->hasMorePages = $this->nextPageToken !== null;

        // Extract items for this page only (don't accumulate in $this->items)
        $pageItems = $data;
        if ($this->responseKey && isset($data[$this->responseKey])) {
            $pageItems = $data[$this->responseKey];
        }

        // Return just this page's items as a collection
        return collect(is_array($pageItems) ? $pageItems : []);
    }

    /**
     * Get all results by iterating through all pages.
     *
     * Warning: This method loads all results into memory at once.
     * For large datasets, consider using lazy loading instead.
     *
     * @param  int|null  $maxItems  Maximum number of items to retrieve (prevents memory issues)
     * @return Collection<int, TValue>
     */
    public function getAllPages(?int $maxItems = null): Collection
    {
        $allResults = collect();
        $itemCount = 0;

        while ($this->hasMorePages) {
            $page = $this->getNextPage();

            // Add items to result collection
            foreach ($page as $item) {
                if ($maxItems && $itemCount >= $maxItems) {
                    break 2; // Break out of both loops
                }
                $allResults->push($item);
                $itemCount++;
            }
        }

        return $allResults;
    }

    /**
     * Transform collection using a DTO's static method.
     *
     * @param  int|null  $maxItems  Maximum number of items to transform (prevents memory issues)
     * @return Collection<int, TValue>
     */
    public function transformUsingDTO(string $dtoClass, string $method = 'collectionFromApiResponse', ?int $maxItems = null): Collection
    {
        $response = $this->getAllPages($maxItems);

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
