<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Repositories;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;

class LabelRepository
{
    public function __construct(
        private readonly LabelResource $resource
    ) {}

    public function findById(string $id): ?Label
    {
        try {
            $response = $this->resource->get($id);

            return Label::fromApiResponse($response->json());
        } catch (NotFoundException) {
            return null;
        }
    }

    public function findByIdOrFail(string $id): Label
    {
        $label = $this->findById($id);

        if ($label === null) {
            throw new NotFoundException("Label with ID {$id} not found");
        }

        return $label;
    }

    public function findByName(string $name): ?Label
    {
        return $this->all()
            ->first(fn (Label $label) => $label->name === $name);
    }

    public function findByNameOrFail(string $name): Label
    {
        $label = $this->findByName($name);

        if ($label === null) {
            throw new NotFoundException("Label with name '{$name}' not found");
        }

        return $label;
    }

    /**
     * @return Collection<int, Label>
     */
    public function all(): Collection
    {
        $response = $this->resource->list();
        $data = $response->json();

        $labels = $data['labels'] ?? [];
        $labelCollection = new Collection;
        foreach ($labels as $labelData) {
            $labelCollection->push(Label::fromApiResponse($labelData));
        }

        return $labelCollection;
    }

    /**
     * @return Collection<int, Label>
     */
    public function systemLabels(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->type === 'system');
    }

    /**
     * @return Collection<int, Label>
     */
    public function userLabels(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->type === 'user');
    }

    /**
     * @return Collection<int, Label>
     */
    public function visibleLabels(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->labelListVisibility === 'labelShow');
    }

    /**
     * @return Collection<int, Label>
     */
    public function hiddenLabels(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->labelListVisibility === 'labelHide');
    }

    /**
     * @return Collection<int, Label>
     */
    public function labelsWithMessages(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->messagesTotal > 0);
    }

    /**
     * @return Collection<int, Label>
     */
    public function labelsWithUnreadMessages(): Collection
    {
        return $this->all()
            ->filter(fn (Label $label) => $label->messagesUnread > 0);
    }

    public function findInbox(): ?Label
    {
        return $this->findById('INBOX');
    }

    public function findSent(): ?Label
    {
        return $this->findById('SENT');
    }

    public function findDrafts(): ?Label
    {
        return $this->findById('DRAFT');
    }

    public function findStarred(): ?Label
    {
        return $this->findById('STARRED');
    }

    public function findImportant(): ?Label
    {
        return $this->findById('IMPORTANT');
    }

    public function findTrash(): ?Label
    {
        return $this->findById('TRASH');
    }

    public function findSpam(): ?Label
    {
        return $this->findById('SPAM');
    }

    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function existsByName(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Label
    {
        $response = $this->resource->create($attributes);

        return Label::fromApiResponse($response->json());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Label
    {
        $response = $this->resource->update($id, $attributes);

        return Label::fromApiResponse($response->json());
    }

    public function delete(string $id): bool
    {
        try {
            $this->resource->delete($id);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        $labels = $this->all();

        return [
            'total_labels' => $labels->count(),
            'system_labels' => $labels->filter(fn (Label $l) => $l->type === 'system')->count(),
            'user_labels' => $labels->filter(fn (Label $l) => $l->type === 'user')->count(),
            'visible_labels' => $labels->filter(fn (Label $l) => $l->labelListVisibility === 'labelShow')->count(),
            'hidden_labels' => $labels->filter(fn (Label $l) => $l->labelListVisibility === 'labelHide')->count(),
            'labels_with_messages' => $labels->filter(fn (Label $l) => $l->messagesTotal > 0)->count(),
            'labels_with_unread' => $labels->filter(fn (Label $l) => $l->messagesUnread > 0)->count(),
            'total_messages' => $labels->sum('messagesTotal'),
            'total_unread' => $labels->sum('messagesUnread'),
        ];
    }
}
