<?php

namespace PartridgeRocks\GmailClient\Data;

use Spatie\LaravelData\Data;

/**
 * Gmail Label Data Object - represents a Gmail label with all properties.
 *
 * This data class encapsulates Gmail label information including system labels
 * (INBOX, SENT, DRAFT, etc.) and custom user-created labels. Provides access
 * to label metadata, visibility settings, color customization, and message/thread counts.
 *
 * Label Types:
 * - System labels: Built-in labels like INBOX, SENT, DRAFT (cannot be modified/deleted)
 * - User labels: Custom labels created by users (can be modified/deleted)
 *
 * Key Properties:
 * - Visibility settings for message and label lists
 * - Color customization (background and text colors)
 * - Message and thread counts (total and unread)
 * - Label type classification
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels#Label
 */
class Label extends Data
{
    /**
     * Initializes a new Label instance with Gmail label properties.
     *
     * @param  string  $id  The unique identifier of the label.
     * @param  string  $name  The display name of the label.
     * @param  string|null  $type  The type of label, if specified.
     * @param  array|null  $messageListVisibility  Visibility settings for message lists, if provided.
     * @param  array|null  $labelListVisibility  Visibility settings for label lists, if provided.
     * @param  int|null  $messagesTotal  Total number of messages with this label, if available.
     * @param  int|null  $messagesUnread  Number of unread messages with this label, if available.
     * @param  int|null  $threadsTotal  Total number of threads with this label, if available.
     * @param  int|null  $threadsUnread  Number of unread threads with this label, if available.
     * @param  string|null  $color  The color associated with the label, if specified.
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
        public string|array|null $color = null
    ) {}

    /**
     * Creates a Label instance from an associative array representing an API response.
     *
     * Maps expected keys from the input array to the corresponding Label properties, assigning null to any missing optional fields.
     *
     * @param  array  $data  Associative array containing label data, typically from a Gmail API response.
     * @return self A new Label instance populated with the provided data.
     */
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
