<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Gmail\Requests\Messages\GetMessageRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ModifyMessageLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\SendMessageRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

/**
 * Gmail Messages Resource - handles message-related API operations.
 *
 * This resource provides methods to interact with Gmail messages including
 * listing, retrieving, sending, and managing message labels through the Gmail API.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages
 */
class MessageResource extends BaseResource
{
    /**
     * List messages in the user's mailbox.
     *
     * @param  array  $query  Optional query parameters:
     *                        - q: string Search query (e.g., 'is:unread', 'from:example@gmail.com')
     *                        - labelIds: array Filter by label IDs
     *                        - maxResults: int Maximum number of messages to return (1-500, default 100)
     *                        - pageToken: string Token for pagination
     *                        - includeSpamTrash: bool Include spam and trash in results
     * @return Response Gmail API response containing message list
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/list
     */
    public function list(array $query = []): Response
    {
        return $this->connector->send(new ListMessagesRequest($query));
    }

    /**
     * Get a specific message by ID.
     *
     * @param  string  $id  The message ID to retrieve
     * @param  array  $query  Optional query parameters:
     *                        - format: string Message format ('minimal', 'full', 'raw', 'metadata')
     *                        - metadataHeaders: array Specific headers to include when format=metadata
     * @return Response Gmail API response containing full message data
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/get
     */
    public function get(string $id, array $query = []): Response
    {
        return $this->connector->send(new GetMessageRequest($id, $query));
    }

    /**
     * Send an email message.
     *
     * @param  array  $data  Message data:
     *                       - raw: string Base64url-encoded RFC2822 formatted message
     *                       - threadId: string (optional) Thread ID to reply to
     * @return Response Gmail API response containing sent message data
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/send
     */
    public function send(array $data): Response
    {
        return $this->connector->send(new SendMessageRequest($data));
    }

    /**
     * Modify labels on a message (add and/or remove labels).
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $addLabelIds  Array of label IDs to add to the message
     * @param  array  $removeLabelIds  Array of label IDs to remove from the message
     * @return Response Gmail API response containing updated message data
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/modify
     */
    public function modifyLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, $addLabelIds, $removeLabelIds));
    }

    /**
     * Add labels to a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $labelIds  Array of label IDs to add (e.g., ['STARRED', 'IMPORTANT'])
     * @return Response Gmail API response containing updated message data
     */
    public function addLabels(string $messageId, array $labelIds): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, $labelIds, []));
    }

    /**
     * Remove labels from a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array  $labelIds  Array of label IDs to remove (e.g., ['UNREAD', 'INBOX'])
     * @return Response Gmail API response containing updated message data
     */
    public function removeLabels(string $messageId, array $labelIds): Response
    {
        return $this->connector->send(new ModifyMessageLabelsRequest($messageId, [], $labelIds));
    }
}
