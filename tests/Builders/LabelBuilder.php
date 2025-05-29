<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Tests\Builders;

use PartridgeRocks\GmailClient\Data\Label;

class LabelBuilder
{
    private string $id = 'test-label-id';
    private string $name = 'Test Label';
    private string $messageListVisibility = 'show';
    private string $labelListVisibility = 'labelShow';
    private string $type = 'user';
    private int $messagesTotal = 0;
    private int $messagesUnread = 0;
    private int $threadsTotal = 0;
    private int $threadsUnread = 0;
    private array $color = [];

    public function withId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function withMessageCounts(int $total, int $unread = 0): self
    {
        $this->messagesTotal = $total;
        $this->messagesUnread = $unread;
        return $this;
    }

    public function withThreadCounts(int $total, int $unread = 0): self
    {
        $this->threadsTotal = $total;
        $this->threadsUnread = $unread;
        return $this;
    }

    public function withColor(array $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function hidden(): self
    {
        $this->messageListVisibility = 'hide';
        return $this;
    }

    public function systemLabel(): self
    {
        $this->type = 'system';
        return $this;
    }

    public function userLabel(): self
    {
        $this->type = 'user';
        return $this;
    }

    public function inbox(): self
    {
        return $this->withId('INBOX')
            ->withName('INBOX')
            ->systemLabel()
            ->withMessageCounts(150, 25);
    }

    public function sent(): self
    {
        return $this->withId('SENT')
            ->withName('SENT')
            ->systemLabel()
            ->withMessageCounts(75, 0);
    }

    public function drafts(): self
    {
        return $this->withId('DRAFT')
            ->withName('DRAFT')
            ->systemLabel()
            ->withMessageCounts(5, 5);
    }

    public function starred(): self
    {
        return $this->withId('STARRED')
            ->withName('STARRED')
            ->systemLabel()
            ->withMessageCounts(12, 3);
    }

    public function build(): Label
    {
        return new Label(
            id: $this->id,
            name: $this->name,
            messageListVisibility: $this->messageListVisibility,
            labelListVisibility: $this->labelListVisibility,
            type: $this->type,
            messagesTotal: $this->messagesTotal,
            messagesUnread: $this->messagesUnread,
            threadsTotal: $this->threadsTotal,
            threadsUnread: $this->threadsUnread,
            color: $this->color
        );
    }

    public function buildApiResponse(): array
    {
        $label = $this->build();
        
        $response = [
            'id' => $label->id,
            'name' => $label->name,
            'messageListVisibility' => $label->messageListVisibility,
            'labelListVisibility' => $label->labelListVisibility,
            'type' => $label->type,
        ];

        if ($label->messagesTotal > 0 || $label->messagesUnread > 0) {
            $response['messagesTotal'] = $label->messagesTotal;
            $response['messagesUnread'] = $label->messagesUnread;
        }

        if ($label->threadsTotal > 0 || $label->threadsUnread > 0) {
            $response['threadsTotal'] = $label->threadsTotal;
            $response['threadsUnread'] = $label->threadsUnread;
        }

        if (!empty($label->color)) {
            $response['color'] = $label->color;
        }

        return $response;
    }

    public static function create(): self
    {
        return new self();
    }

    public static function sample(): Label
    {
        return self::create()
            ->withName('Sample Label')
            ->withMessageCounts(10, 2)
            ->build();
    }

    public static function collection(int $count = 5): array
    {
        $labels = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $labels[] = self::create()
                ->withId("label-{$i}")
                ->withName("Label {$i}")
                ->withMessageCounts($i * 10, $i * 2)
                ->build();
        }

        return $labels;
    }
}