<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Repositories;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class MessageRepository
{
    public function __construct(
        private readonly MessageResource $resource
    ) {}

    public function findById(string $id): ?Email
    {
        try {
            $response = $this->resource->get($id);

            return Email::fromApiResponse($response->json());
        } catch (NotFoundException) {
            return null;
        }
    }

    public function findByIdOrFail(string $id): Email
    {
        $email = $this->findById($id);

        if ($email === null) {
            throw new NotFoundException("Email with ID {$id} not found");
        }

        return $email;
    }

    public function findWhere(array $criteria): Collection
    {
        $response = $this->resource->list($criteria);
        $data = $response->json();

        return collect($data['messages'] ?? [])
            ->map(fn (array $messageData) => Email::fromApiResponse($messageData));
    }

    public function findUnread(int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'q' => 'is:unread',
            'maxResults' => $maxResults,
        ]);
    }

    public function findStarred(int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'q' => 'is:starred',
            'maxResults' => $maxResults,
        ]);
    }

    public function findInLabel(string $label, int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'labelIds' => [$label],
            'maxResults' => $maxResults,
        ]);
    }

    public function findFromSender(string $email, int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'q' => "from:{$email}",
            'maxResults' => $maxResults,
        ]);
    }

    public function findBySubject(string $subject, int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'q' => "subject:{$subject}",
            'maxResults' => $maxResults,
        ]);
    }

    public function findInDateRange(\DateTimeInterface $from, \DateTimeInterface $to, int $maxResults = 25): Collection
    {
        $fromFormatted = $from->format('Y/m/d');
        $toFormatted = $to->format('Y/m/d');

        return $this->findWhere([
            'q' => "after:{$fromFormatted} before:{$toFormatted}",
            'maxResults' => $maxResults,
        ]);
    }

    public function search(string $query, int $maxResults = 25): Collection
    {
        return $this->findWhere([
            'q' => $query,
            'maxResults' => $maxResults,
        ]);
    }

    public function count(array $criteria = []): int
    {
        $response = $this->resource->list(array_merge($criteria, ['maxResults' => 1]));
        $data = $response->json();

        return $data['resultSizeEstimate'] ?? 0;
    }

    public function countUnread(): int
    {
        return $this->count(['q' => 'is:unread']);
    }

    public function countStarred(): int
    {
        return $this->count(['q' => 'is:starred']);
    }

    public function countInLabel(string $label): int
    {
        return $this->count(['labelIds' => [$label]]);
    }

    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }
}
