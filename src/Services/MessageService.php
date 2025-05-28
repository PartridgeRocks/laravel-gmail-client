<?php

namespace PartridgeRocks\GmailClient\Services;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Constants\GmailConstants;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\Gmail\ExceptionHandling;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class MessageService implements MessageServiceInterface
{
    use ExceptionHandling;
    use GmailClientHelpers;

    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * List messages with various options.
     *
     * @param  array<string, mixed>  $query  Search/filter parameters (e.g., ['q' => 'is:unread'])
     * @param  bool  $paginate  Whether to return a paginator instance
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $lazy  Whether to return a lazy collection
     * @param  bool  $fullDetails  Whether to fetch full message details
     * @return mixed Collection, Paginator, or LazyCollection based on parameters
     *
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        if ($lazy) {
            return $this->lazyLoadMessages($query, $maxResults, $fullDetails);
        }

        if ($paginate) {
            return $this->paginateMessages($query, $maxResults);
        }

        $response = $this->getMessageResource()->list($query);
        $data = $response->json();

        /** @var array<int, array<string, mixed>> $messagesData */
        $messagesData = $data['messages'] ?? [];
        $messages = collect($messagesData);

        return $messages->map(function (array $message) {
            return $this->getMessage($message['id']);
        });
    }

    /**
     * Create a paginator for messages.
     *
     * @param  array<string, mixed>  $query  Search/filter parameters
     * @param  int  $maxResults  Maximum number of results per page
     * @return GmailPaginator<Email> Paginator instance for messages
     */
    public function paginateMessages(array $query = [], int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS): GmailPaginator
    {
        return new GmailPaginator(
            $this->connector,
            ListMessagesRequest::class,
            'messages',
            $maxResults
        );
    }

    /**
     * Create a lazy-loading collection for messages.
     * Note: Lazy loading should be handled by GmailClient directly.
     *
     * @param  array<string, mixed>  $query  Search/filter parameters
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $fullDetails  Whether to fetch full message details
     * @return \Illuminate\Support\LazyCollection<int, Email> Empty lazy collection (implementation placeholder)
     */
    public function lazyLoadMessages(array $query = [], int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS, bool $fullDetails = true): \Illuminate\Support\LazyCollection
    {
        // Return empty lazy collection since lazy loading requires GmailClient instance
        return collect()->lazy();
    }

    /**
     * Get a specific message.
     *
     * @param  string  $id  The message ID to retrieve
     * @return Email The message data
     *
     * @throws NotFoundException When the message is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     * @throws ValidationException When the request is malformed
     */
    public function getMessage(string $id): Email
    {
        $response = $this->getMessageResource()->get($id, ['format' => 'full']);

        $this->handleApiResponse($response, 'message', $id);

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Send an email message.
     *
     * @param  string  $to  Recipient email address
     * @param  string  $subject  Email subject line
     * @param  string  $body  Email body content
     * @param  array<string, mixed>  $options  Optional settings:
     *                          - from: string Sender email (default: config value)
     *                          - from_name: string Sender display name
     *                          - cc: array CC recipients
     *                          - bcc: array BCC recipients
     *                          - html: bool Whether body is HTML (default: false)
     * @return Email The sent message data
     *
     * @throws ValidationException When email addresses or required fields are invalid
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email
    {
        // Validate email address
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw \PartridgeRocks\GmailClient\Exceptions\ValidationException::invalidEmailAddress($to);
        }

        // Validate required fields
        if (empty($subject)) {
            throw \PartridgeRocks\GmailClient\Exceptions\ValidationException::missingRequiredField('subject');
        }

        $rawMessage = $this->createEmailRaw($to, $subject, $body, $options);

        $response = $this->getMessageResource()->send(['raw' => $this->base64UrlEncode($rawMessage)]);

        $this->handleApiResponse($response, 'message');

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Add labels to a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array<string>  $labelIds  Array of label IDs to add
     * @return Email The updated message data
     *
     * @throws NotFoundException When the message is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     * @throws ValidationException When label IDs are invalid
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email
    {
        return $this->modifyMessageLabels($messageId, $labelIds, []);
    }

    /**
     * Remove labels from a message.
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array<string>  $labelIds  Array of label IDs to remove
     * @return Email The updated message data
     *
     * @throws NotFoundException When the message is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     * @throws ValidationException When label IDs are invalid
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email
    {
        return $this->modifyMessageLabels($messageId, [], $labelIds);
    }

    /**
     * Modify message labels (add and/or remove).
     *
     * @param  string  $messageId  The message ID to modify
     * @param  array<string>  $addLabelIds  Array of label IDs to add to the message
     * @param  array<string>  $removeLabelIds  Array of label IDs to remove from the message
     * @return Email The updated message data
     *
     * @throws NotFoundException When the message is not found
     * @throws AuthenticationException When authentication fails or token is invalid
     * @throws RateLimitException When API rate limit is exceeded
     * @throws ValidationException When label IDs are invalid
     */
    public function modifyMessageLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Email
    {
        $response = $this->getMessageResource()->modifyLabels($messageId, $addLabelIds, $removeLabelIds);

        $this->handleApiResponse($response, 'message', $messageId);

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Safely get a message, returning null on failure.
     *
     * @param  string  $id  The message ID to retrieve
     * @return Email|null The message data or null if not found/error
     */
    public function safeGetMessage(string $id): ?Email
    {
        return $this->safeCall(
            callback: fn () => $this->getMessage($id),
            fallback: null,
            operation: 'get message',
            context: ['message_id' => $id]
        );
    }

    /**
     * Safely list messages, returning empty collection on failure.
     *
     * @param  array<string, mixed>  $query  Search/filter parameters
     * @param  bool  $paginate  Whether to return a paginator instance
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $lazy  Whether to return a lazy collection
     * @param  bool  $fullDetails  Whether to fetch full message details
     * @return mixed Collection, Paginator, or LazyCollection based on parameters
     */
    public function safeListMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = GmailConstants::DEFAULT_MAX_RESULTS,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        return $this->safeCall(
            callback: fn () => $this->listMessages($query, $paginate, $maxResults, $lazy, $fullDetails),
            fallback: $this->getEmptyMessagesStructure($query, $paginate, $lazy, $maxResults),
            operation: 'list messages',
            context: ['query' => $query, 'paginate' => $paginate, 'lazy' => $lazy, 'maxResults' => $maxResults]
        );
    }

    /**
     * Get appropriate empty structure for messages based on requested format.
     *
     * @param  array<string, mixed>  $query
     */
    private function getEmptyMessagesStructure(array $query, bool $paginate, bool $lazy, int $maxResults): mixed
    {
        if ($lazy) {
            return collect()->lazy();
        }

        if ($paginate) {
            return $this->paginateMessages($query, $maxResults);
        }

        return collect();
    }

    /**
     * Create raw email content.
     *
     * @param  array<string, mixed>  $options
     */
    private function createEmailRaw(string $to, string $subject, string $body, array $options = []): string
    {
        $fromEmail = $options['from'] ?? config('gmail-client.from_email');
        $fromName = $options['from_name'] ?? null;
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $isHtml = $options['html'] ?? false;

        if (empty($fromEmail)) {
            throw \PartridgeRocks\GmailClient\Exceptions\ValidationException::missingRequiredField('from_email');
        }

        // Validate sender email address
        if (! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw \PartridgeRocks\GmailClient\Exceptions\ValidationException::invalidEmailAddress($fromEmail);
        }

        $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

        $headers = [
            "From: {$from}",
            "To: {$to}",
            "Subject: {$subject}",
        ];

        if (! empty($cc)) {
            $headers[] = 'Cc: '.implode(', ', $cc);
        }

        if (! empty($bcc)) {
            $headers[] = 'Bcc: '.implode(', ', $bcc);
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = $isHtml
            ? 'Content-Type: text/html; charset=utf-8'
            : 'Content-Type: text/plain; charset=utf-8';

        return implode("\r\n", $headers)."\r\n\r\n".$body;
    }


    /**
     * Get the message resource.
     */
    private function getMessageResource(): MessageResource
    {
        return new MessageResource($this->connector);
    }
}
