<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

/**
 * Gmail List Messages Request - retrieves a list of messages in the user's mailbox.
 *
 * This request returns message IDs and basic metadata. Use GetMessageRequest
 * to fetch full details for specific messages. Supports powerful query syntax
 * for filtering messages by various criteria.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/list
 */
class ListMessagesRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::GET;

    protected array $customQuery = [];

    /**
     * Create a new list messages request.
     *
     * @param  array  $query  Optional query parameters:
     *                        - q: string Gmail search query syntax:
     *                        - 'is:unread' - Unread messages
     *                        - 'is:starred' - Starred messages
     *                        - 'from:example@gmail.com' - From specific sender
     *                        - 'subject:"Test Subject"' - Messages with subject
     *                        - 'has:attachment' - Messages with attachments
     *                        - 'after:2023/01/01' - Messages after date
     *                        - 'before:2023/12/31' - Messages before date
     *                        - labelIds: array Filter by specific label IDs
     *                        - maxResults: int Max results (1-500, default 100)
     *                        - pageToken: string Pagination token
     *                        - includeSpamTrash: bool Include spam/trash (default false)
     */
    public function __construct(array $query = [])
    {
        $this->customQuery = $query;
    }

    /**
     * Resolve the API endpoint for this request.
     *
     * @return string Gmail API endpoint for listing messages
     */
    public function resolveEndpoint(): string
    {
        return '/users/me/messages';
    }

    /**
     * Get the default query parameters for this request.
     *
     * @return array Query parameters for filtering and pagination
     */
    public function defaultQuery(): array
    {
        return $this->customQuery;
    }
}
