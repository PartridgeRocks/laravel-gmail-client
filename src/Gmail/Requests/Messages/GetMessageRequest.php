<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

/**
 * Gmail Get Message Request - retrieves a specific message by ID.
 *
 * This request fetches detailed information about a single Gmail message,
 * including headers, body content, attachments, and metadata.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/get
 */
class GetMessageRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    protected string $messageId;

    protected array $customQuery = [];

    /**
     * Create a new get message request.
     *
     * @param  string  $id  The Gmail message ID to retrieve
     * @param  array  $query  Optional query parameters:
     *                        - format: string Message format ('minimal'|'full'|'raw'|'metadata')
     *                        - minimal: Returns only message ID and labels
     *                        - full: Returns full message data (default)
     *                        - raw: Returns raw RFC2822 message
     *                        - metadata: Returns only headers and metadata
     *                        - metadataHeaders: array Specific headers when format=metadata
     */
    public function __construct(string $id, array $query = [])
    {
        $this->messageId = $id;
        $this->customQuery = $query;
    }

    /**
     * Resolve the API endpoint for this request.
     *
     * @return string Gmail API endpoint for getting a specific message
     */
    public function resolveEndpoint(): string
    {
        return '/users/me/messages/'.$this->messageId;
    }

    /**
     * Get the default query parameters for this request.
     *
     * @return array Query parameters passed to Gmail API
     */
    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}
