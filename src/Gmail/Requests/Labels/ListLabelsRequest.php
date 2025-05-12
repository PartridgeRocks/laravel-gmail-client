<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListLabelsRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/users/me/labels';
    }
}