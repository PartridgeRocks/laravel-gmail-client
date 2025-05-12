<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Http\BaseResource;
use Saloon\Http\Response;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\GetMessageRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\SendMessageRequest;

class MessageResource extends BaseResource
{
    /**
     * List messages in the user's mailbox.
     */
    public function list(array $query = []): Response
    {
        return $this->connector->send(new ListMessagesRequest($query));
    }

    /**
     * Get a specific message.
     */
    public function get(string $id, array $query = []): Response
    {
        return $this->connector->send(new GetMessageRequest($id, $query));
    }

    /**
     * Send a message.
     */
    public function send(array $data): Response
    {
        return $this->connector->send(new SendMessageRequest($data));
    }
}