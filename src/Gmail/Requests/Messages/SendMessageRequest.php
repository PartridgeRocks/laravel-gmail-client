<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class SendMessageRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected array $messageData;

    public function __construct(array $data)
    {
        $this->messageData = $data;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/messages/send';
    }

    public function defaultBody(): array
    {
        return $this->messageData;
    }
}
