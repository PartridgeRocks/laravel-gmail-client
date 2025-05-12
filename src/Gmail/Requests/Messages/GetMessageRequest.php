<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class GetMessageRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    protected string $messageId;

    protected array $customQuery = [];

    public function __construct(string $id, array $query = [])
    {
        $this->messageId = $id;
        $this->customQuery = $query;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/messages/'.$this->messageId;
    }

    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}
