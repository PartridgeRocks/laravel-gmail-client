<?php

namespace PartridgeRocks\GmailClient\Data;

use Spatie\LaravelData\Data;

class Label extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $type = null,
        public ?array $messageListVisibility = null,
        public ?array $labelListVisibility = null,
        public ?int $messagesTotal = null,
        public ?int $messagesUnread = null,
        public ?int $threadsTotal = null,
        public ?int $threadsUnread = null,
        public ?string $color = null
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            type: $data['type'] ?? null,
            messageListVisibility: $data['messageListVisibility'] ?? null,
            labelListVisibility: $data['labelListVisibility'] ?? null,
            messagesTotal: $data['messagesTotal'] ?? null,
            messagesUnread: $data['messagesUnread'] ?? null,
            threadsTotal: $data['threadsTotal'] ?? null,
            threadsUnread: $data['threadsUnread'] ?? null,
            color: $data['color'] ?? null
        );
    }
}
