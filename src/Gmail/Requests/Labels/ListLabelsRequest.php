<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class ListLabelsRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    protected array $customQuery = [];

    public function __construct(array $query = [])
    {
        $this->customQuery = $query;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels';
    }

    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}
