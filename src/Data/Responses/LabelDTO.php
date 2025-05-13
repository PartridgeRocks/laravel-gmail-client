<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class LabelDTO extends ResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $type = null,
        public string|array|null $messageListVisibility = null,
        public string|array|null $labelListVisibility = null,
        public ?int $messagesTotal = null,
        public ?int $messagesUnread = null,
        public ?int $threadsTotal = null,
        public ?int $threadsUnread = null,
        public string|array|null $color = null,
        ?string $etag = null,
        ?Carbon $responseTime = null
    ) {
        parent::__construct($etag, $responseTime);
    }

    public static function fromApiResponse(array $data): static
    {
        return new static(
            id: $data['id'],
            name: $data['name'],
            type: $data['type'] ?? null,
            messageListVisibility: $data['messageListVisibility'] ?? null,
            labelListVisibility: $data['labelListVisibility'] ?? null,
            messagesTotal: $data['messagesTotal'] ?? null,
            messagesUnread: $data['messagesUnread'] ?? null,
            threadsTotal: $data['threadsTotal'] ?? null,
            threadsUnread: $data['threadsUnread'] ?? null,
            color: $data['color'] ?? null,
            etag: $data['etag'] ?? null,
            responseTime: Carbon::now()
        );
    }

    /**
     * Create a collection of LabelDTO objects from a list response
     */
    public static function collectionFromApiResponse(array $data): Collection
    {
        return collect($data['labels'] ?? [])->map(function ($label) {
            return static::fromApiResponse($label);
        });
    }
}
