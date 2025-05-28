<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class ModifyMessageLabelsRequest extends BaseRequest implements HasBody
{
    use HasJsonBody;

    // Define the HTTP method for Gmail API messages.modify
    protected Method $method = Method::POST;

    protected string $messageId;

    /** @var array<string> */
    protected array $addLabelIds;

    /** @var array<string> */
    protected array $removeLabelIds;

    /**
     * Create a new modify message labels request
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array<string>  $addLabelIds  Array of label IDs to add
     * @param  array<string>  $removeLabelIds  Array of label IDs to remove
     */
    public function __construct(string $messageId, array $addLabelIds = [], array $removeLabelIds = [])
    {
        $this->messageId = $messageId;
        $this->addLabelIds = $addLabelIds;
        $this->removeLabelIds = $removeLabelIds;
    }

    /**
     * Define the endpoint for the Gmail API messages.modify
     */
    public function resolveEndpoint(): string
    {
        return '/users/me/messages/'.$this->messageId.'/modify';
    }

    /**
     * Define the request body for label modification
     *
     * @return array<string, array<string>>
     */
    public function defaultBody(): array
    {
        $body = [];

        if (! empty($this->addLabelIds)) {
            $body['addLabelIds'] = $this->addLabelIds;
        }

        if (! empty($this->removeLabelIds)) {
            $body['removeLabelIds'] = $this->removeLabelIds;
        }

        return $body;
    }
}
