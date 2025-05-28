<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

class UpdateLabelRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::PUT;

    protected string $labelId;

    /** @var array<string, mixed> */
    protected array $labelData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(string $id, array $data)
    {
        $this->labelId = $id;
        $this->labelData = $data;
    }

    public function resolveEndpoint(): string
    {
        return '/users/me/labels/'.$this->labelId;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return $this->labelData;
    }
}
