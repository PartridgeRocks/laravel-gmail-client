<?php

namespace PartridgeRocks\GmailClient\Services;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class MessageService
{
    use GmailClientHelpers;

    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * List messages with various options.
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
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

        $messages = collect($data['messages'] ?? []);

        return $messages->map(function ($message) {
            return $this->getMessage($message['id']);
        });
    }

    /**
     * Create a paginator for messages.
     */
    public function paginateMessages(array $query = [], int $maxResults = 100): GmailPaginator
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
     */
    public function lazyLoadMessages(array $query = [], int $maxResults = 100, bool $fullDetails = true): \Illuminate\Support\LazyCollection
    {
        // Return empty lazy collection since lazy loading requires GmailClient instance
        return collect()->lazy();
    }

    /**
     * Get a specific message.
     *
     * @throws NotFoundException
     * @throws AuthenticationException
     * @throws RateLimitException
     */
    public function getMessage(string $id): Email
    {
        $response = $this->getMessageResource()->get($id, ['format' => 'full']);

        if ($response->status() === 404) {
            throw NotFoundException::message($id);
        }

        if ($response->status() === 401) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === 429) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');
            throw RateLimitException::quotaExceeded($retryAfter);
        }

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Send an email.
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

        if ($response->status() === 400) {
            throw new \PartridgeRocks\GmailClient\Exceptions\ValidationException('Invalid email data provided');
        }

        if ($response->status() === 401) {
            throw AuthenticationException::invalidToken();
        }

        if ($response->status() === 429) {
            $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After') ?? '0');

            throw RateLimitException::quotaExceeded($retryAfter);
        }

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Add labels to a message.
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email
    {
        return $this->modifyMessageLabels($messageId, $labelIds, []);
    }

    /**
     * Remove labels from a message.
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email
    {
        return $this->modifyMessageLabels($messageId, [], $labelIds);
    }

    /**
     * Modify message labels (add and/or remove).
     */
    public function modifyMessageLabels(string $messageId, array $addLabelIds = [], array $removeLabelIds = []): Email
    {
        $response = $this->getMessageResource()->modifyLabels($messageId, $addLabelIds, $removeLabelIds);

        if ($response->status() === 404) {
            throw NotFoundException::message($messageId);
        }

        if ($response->status() === 401) {
            throw AuthenticationException::invalidToken();
        }

        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Safely get a message, returning null on failure.
     */
    public function safeGetMessage(string $id): ?Email
    {
        try {
            return $this->getMessage($id);
        } catch (NotFoundException $e) {
            // Message not found is expected in some cases - don't log as warning
            return null;
        } catch (\Exception $e) {
            logger()->warning("Gmail operation failed: get message - {$e->getMessage()}", [
                'operation' => 'get message',
                'error_type' => get_class($e),
                'message_id' => $id,
            ]);

            return null;
        }
    }

    /**
     * Safely list messages, returning empty collection on failure.
     */
    public function safeListMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
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
     * Execute a callable safely with error handling and logging.
     */
    private function safeCall(callable $callback, mixed $fallback, string $operation, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            logger()->warning("Gmail operation failed: {$operation} - {$e->getMessage()}", array_merge([
                'operation' => $operation,
                'error_type' => get_class($e),
            ], $context));

            return $fallback;
        }
    }

    /**
     * Get appropriate empty structure for messages based on requested format.
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
     * Parse the Retry-After header value.
     */
    private function parseRetryAfterHeader(string $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return max(0, $timestamp - time());
        }

        return 60; // Default to 60 seconds
    }

    /**
     * Get the message resource.
     */
    private function getMessageResource(): MessageResource
    {
        return new MessageResource($this->connector);
    }
}
