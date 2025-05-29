<?php

namespace PartridgeRocks\GmailClient;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Contracts\Composite\GmailReadInterface;
use PartridgeRocks\GmailClient\Contracts\Composite\GmailSafeInterface;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;

/**
 * Read-only Gmail client for analytics and reporting.
 *
 * This client provides only read operations and safe methods, making it
 * perfect for analytics dashboards, reporting systems, and background
 * processing where you don't need write access.
 *
 * Benefits:
 * - Lighter weight (fewer dependencies)
 * - Safer for read-only scenarios
 * - Clear separation of concerns
 * - Better for role-based access control
 */
class GmailReadOnlyClient implements GmailReadInterface, GmailSafeInterface
{
    public function __construct(
        private GmailClient $client
    ) {}

    public function listMessages(
        array $query = [],
        bool $paginate = false,
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        return $this->client->listMessages($query, $paginate, $maxResults, $lazy, $fullDetails);
    }

    public function getMessage(string $id): Email
    {
        return $this->client->getMessage($id);
    }

    public function listLabels(bool $paginate = false, bool $lazy = false): mixed
    {
        return $this->client->listLabels($paginate, $lazy);
    }

    public function getLabel(string $id): Label
    {
        return $this->client->getLabel($id);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function getAccountStatistics(array $options = []): array
    {
        return $this->client->getAccountStatistics($options);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountHealth(): array
    {
        return $this->client->getAccountHealth();
    }

    public function isConnected(): bool
    {
        return $this->client->isConnected();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountSummary(): array
    {
        return $this->client->getAccountSummary();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<int, Email>|LazyCollection<int, Email>
     */
    public function safeListMessages(
        array $query = [],
        ?int $maxResults = null,
        bool $lazy = false,
        bool $fullDetails = true
    ): Collection|LazyCollection {
        return $this->client->safeListMessages($query, $maxResults, $lazy, $fullDetails);
    }

    public function safeGetMessage(string $id): ?Email
    {
        return $this->client->safeGetMessage($id);
    }

    /**
     * @return Collection<int, Label>|LazyCollection<int, Label>
     */
    public function safeListLabels(bool $lazy = false, bool $paginate = false): Collection|LazyCollection
    {
        return $this->client->safeListLabels($lazy, $paginate);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function safeGetAccountStatistics(array $options = []): array
    {
        return $this->client->safeGetAccountStatistics($options);
    }
}
