<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Tests\Builders;

use Carbon\Carbon;
use PartridgeRocks\GmailClient\Data\Email;

class EmailBuilder
{
    private string $id = 'test-email-id';
    private string $threadId = 'test-thread-id';
    private array $labelIds = ['INBOX'];
    private string $snippet = 'This is a test email snippet...';
    private array $payload = [];
    private int $sizeEstimate = 1024;
    private ?Carbon $internalDate = null;
    private string $subject = 'Test Email Subject';
    private string $from = 'test@example.com';
    private string $to = 'recipient@example.com';
    private string $body = 'This is the test email body content.';
    private array $headers = [];
    private array $attachments = [];

    public function withId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withThreadId(string $threadId): self
    {
        $this->threadId = $threadId;

        return $this;
    }

    public function withLabels(array $labelIds): self
    {
        $this->labelIds = $labelIds;

        return $this;
    }

    public function withSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function withFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function withTo(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function withBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function withSnippet(string $snippet): self
    {
        $this->snippet = $snippet;

        return $this;
    }

    public function withSize(int $sizeEstimate): self
    {
        $this->sizeEstimate = $sizeEstimate;

        return $this;
    }

    public function withDate(Carbon $date): self
    {
        $this->internalDate = $date;

        return $this;
    }

    public function unread(): self
    {
        $this->labelIds = array_merge($this->labelIds, ['UNREAD']);

        return $this;
    }

    public function starred(): self
    {
        $this->labelIds = array_merge($this->labelIds, ['STARRED']);

        return $this;
    }

    public function important(): self
    {
        $this->labelIds = array_merge($this->labelIds, ['IMPORTANT']);

        return $this;
    }

    public function draft(): self
    {
        $this->labelIds = ['DRAFT'];

        return $this;
    }

    public function sent(): self
    {
        $this->labelIds = ['SENT'];

        return $this;
    }

    public function withAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function build(): Email
    {
        $headers = array_merge([
            ['name' => 'Subject', 'value' => $this->subject],
            ['name' => 'From', 'value' => $this->from],
            ['name' => 'To', 'value' => $this->to],
        ], $this->headers);

        $payload = array_merge([
            'headers' => $headers,
            'body' => ['data' => base64_encode($this->body)],
            'parts' => $this->attachments,
        ], $this->payload);

        return new Email(
            id: $this->id,
            threadId: $this->threadId,
            labelIds: array_unique($this->labelIds),
            snippet: $this->snippet,
            payload: $payload,
            sizeEstimate: $this->sizeEstimate,
            internalDate: $this->internalDate ?? Carbon::now(),
            subject: $this->subject,
            from: $this->from,
            to: $this->to,
            body: $this->body,
            headers: $headers,
            attachments: $this->attachments
        );
    }

    public function buildApiResponse(): array
    {
        $email = $this->build();

        return [
            'id' => $email->id,
            'threadId' => $email->threadId,
            'labelIds' => $email->labelIds,
            'snippet' => $email->snippet,
            'payload' => $email->payload,
            'sizeEstimate' => $email->sizeEstimate,
            'internalDate' => $email->internalDate->timestamp * 1000, // Gmail uses milliseconds
        ];
    }

    public static function create(): self
    {
        return new self;
    }

    public static function sample(): Email
    {
        return self::create()
            ->withSubject('Sample Email')
            ->withFrom('sender@example.com')
            ->withTo('recipient@example.com')
            ->withBody('This is a sample email for testing purposes.')
            ->build();
    }

    public static function unreadSample(): Email
    {
        return self::create()
            ->withSubject('Unread Email')
            ->unread()
            ->build();
    }

    public static function starredSample(): Email
    {
        return self::create()
            ->withSubject('Starred Email')
            ->starred()
            ->build();
    }
}
