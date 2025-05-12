<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CreateLabelRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected array $labelData;

    public function __construct(array $data) {
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