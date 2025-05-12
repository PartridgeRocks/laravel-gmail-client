<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use Saloon\Enums\Method;
use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;

class DeleteLabelRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::DELETE;

    protected string $labelId;

    public function __construct(string $id) {
        $this->labelId = $id;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels/'.$this->labelId;
    }
}