<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class ListLabelsRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    /** @var array<string, mixed> */
    protected array $customQuery = [];

    /**
     * @param array<string, mixed> $query
     */
    public function __construct(array $query = [])
    {
        $this->customQuery = $query;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}
