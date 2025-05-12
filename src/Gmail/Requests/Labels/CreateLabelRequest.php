<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class CreateLabelRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected array $labelData;

    public function __construct(array $data)
    {
        $this->labelData = $data;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels';
    }

    public function defaultBody(): array
    {
        return $this->labelData;
    }
}
