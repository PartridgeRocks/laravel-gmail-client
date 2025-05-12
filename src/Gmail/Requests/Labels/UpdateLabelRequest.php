<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class UpdateLabelRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::PUT;

    protected string $labelId;
    protected array $labelData;

    public function __construct(string $id, array $data) {
        $this->labelId = $id;
        $this->labelData = $data;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels/'.$this->labelId;
    }

    public function defaultBody(): array
    {
        return $this->labelData;
    }
}