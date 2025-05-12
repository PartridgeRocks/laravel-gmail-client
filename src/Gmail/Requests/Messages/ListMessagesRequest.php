<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListMessagesRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    protected array $customQuery = [];

    public function __construct(array $query = []) {
        $this->customQuery = $query;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/messages';
    }

    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}