<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class EmailDTO extends ResponseDTO
{
    /**
     * @param  array<int, string>  $labelIds
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>|null  $headers
     * @param  array<int, string>|null  $to
     * @param  array<int, string>|null  $cc
     * @param  array<int, string>|null  $bcc
     */
    public function __construct(
        public string $id,
        public string $threadId,
        public array $labelIds,
        public ?string $snippet,
        public array $payload,
        public int $sizeEstimate,
        #[WithCast(DateTimeInterfaceCast::class)]
        public Carbon $internalDate,
        public ?array $headers = null,
        public ?string $body = null,
        public ?string $subject = null,
        public ?string $from = null,
        public ?array $to = null,
        public ?array $cc = null,
        public ?array $bcc = null,
        ?string $etag = null,
        ?Carbon $responseTime = null
    ) {
        parent::__construct($etag, $responseTime);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): static
    {
        // Extract header info for easier access
        $headers = [];
        $subject = null;
        $from = null;
        $to = null;
        $cc = null;
        $bcc = null;
        $body = null;

        if (isset($data['payload']['headers'])) {
            foreach ($data['payload']['headers'] as $header) {
                $headers[$header['name']] = $header['value'];

                switch ($header['name']) {
                    case 'Subject':
                        $subject = $header['value'];
                        break;
                    case 'From':
                        $from = $header['value'];
                        break;
                    case 'To':
                        $to = explode(',', $header['value']);
                        break;
                    case 'Cc':
                        $cc = explode(',', $header['value']);
                        break;
                    case 'Bcc':
                        $bcc = explode(',', $header['value']);
                        break;
                }
            }
        }

        // Extract body
        if (isset($data['payload']['body']['data'])) {
            $body = base64_decode(strtr($data['payload']['body']['data'], '-_', '+/'));
        } elseif (isset($data['payload']['parts'])) {
            foreach ($data['payload']['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain' && isset($part['body']['data'])) {
                    $body = base64_decode(strtr($part['body']['data'], '-_', '+/'));
                    break;
                }
            }
        }

        return new static(
            id: $data['id'],
            threadId: $data['threadId'],
            labelIds: $data['labelIds'] ?? [],
            snippet: $data['snippet'] ?? null,
            payload: $data['payload'] ?? [],
            sizeEstimate: $data['sizeEstimate'],
            internalDate: Carbon::createFromTimestampMs($data['internalDate']),
            headers: $headers,
            body: $body,
            subject: $subject,
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            etag: $data['etag'] ?? null,
            responseTime: Carbon::now()
        );
    }

    /**
     * Create a collection of EmailDTO objects from a list response
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, self>
     */
    public static function collectionFromApiResponse(array $data): Collection
    {
        /** @var array<int, array<string, mixed>> $messagesData */
        $messagesData = $data['messages'] ?? [];
        $messages = collect($messagesData);

        if (empty($messages)) {
            /** @var Collection<int, EmailDTO> $emptyCollection */
            $emptyCollection = collect();

            return $emptyCollection;
        }

        return $messages->map(function ($message) {
            // If we only have the ID, we need to return a partial DTO
            if (! isset($message['payload'])) {
                return new static(
                    id: $message['id'],
                    threadId: $message['threadId'] ?? '',
                    labelIds: [],
                    snippet: null,
                    payload: [],
                    sizeEstimate: 0,
                    internalDate: Carbon::now(),
                    etag: null,
                    responseTime: Carbon::now()
                );
            }

            return static::fromApiResponse($message);
        });
    }
}
