<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Messages;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

/**
 * Gmail Send Message Request - sends an email message through Gmail API.
 *
 * This request sends an email message using the Gmail API. The message must be
 * properly formatted as RFC2822 and base64url-encoded. Supports replies by
 * specifying a threadId to maintain conversation threading.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages/send
 */
class SendMessageRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected array $messageData;

    /**
     * Create a new send message request.
     *
     * @param  array  $data  Message data:
     *                       - raw: string Base64url-encoded RFC2822 formatted message (required)
     *                       Must include headers: From, To, Subject, Date, Message-ID
     *                       Body content should be properly encoded for HTML/plain text
     *                       - threadId: string Optional thread ID for replies/conversation threading
     *                       Use this to reply to existing conversations
     */
    public function __construct(array $data)
    {
        $this->messageData = $data;
    }

    /**
     * Resolve the API endpoint for this request.
     *
     * @return string Gmail API endpoint for sending messages
     */
    public function resolveEndpoint(): string
    {
        return '/users/me/messages/send';
    }

    /**
     * Get the request body containing the message data.
     *
     * @return array Message data to send to Gmail API
     */
    public function defaultBody(): array
    {
        return $this->messageData;
    }
}
