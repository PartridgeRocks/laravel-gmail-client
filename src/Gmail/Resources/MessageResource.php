<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Gmail\Requests\Messages\GetMessageRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ModifyMessageLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\SendMessageRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

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

    /**
     * Modify labels on a message (add and/or remove labels).
     */
    public function modifyLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, $addLabelIds, $removeLabelIds));
    }

    /**
     * Add labels to a message.
     */
    public function addLabels(string $messageId, array $labelIds): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, $labelIds, []));
    }

    /**
     * Remove labels from a message.
     */
    public function removeLabels(string $messageId, array $labelIds): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, [], $labelIds));
    }
}
