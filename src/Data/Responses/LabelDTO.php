<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class LabelDTO extends ResponseDTO
{
    /**
     * @param string|array<string, string>|null $messageListVisibility
     * @param string|array<string, string>|null $labelListVisibility
     * @param string|array<string, string>|null $color
     */
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

    /**
     * @param array<string, mixed> $data
     */
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
     *
     * @param array<string, mixed> $data
     * @return Collection<int, self>
     */
    public static function collectionFromApiResponse(array $data): Collection
    {
        /** @var array<int, array<string, mixed>> $labelsData */
        $labelsData = $data['labels'] ?? [];
        return collect($labelsData)->map(function (array $label) {
            return static::fromApiResponse($label);
        });
    }
}
