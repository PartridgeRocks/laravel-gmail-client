<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Gmail\Requests\Labels\CreateLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\DeleteLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\GetLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\ListLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\UpdateLabelRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class LabelResource extends BaseResource
{
    /**
     * List all labels.
     */
    public function list(): Response
    {
        return $this->connector->send(new ListLabelsRequest);
    }

    /**
     * Get a specific label.
     */
    public function get(string $id): Response
    {
        return $this->connector->send(new GetLabelRequest($id));
    }

    /**
     * Create a new label.
     */
    public function create(array $data): Response
    {
        return $this->connector->send(new CreateLabelRequest($data));
    }

    /**
     * Update a label.
     */
    public function update(string $id, array $data): Response
    {
        return $this->connector->send(new UpdateLabelRequest($id, $data));
    }

    /**
     * Delete a label.
     */
    public function delete(string $id): Response
    {
        return $this->connector->send(new DeleteLabelRequest($id));
    }
}
