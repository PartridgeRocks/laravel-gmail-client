<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Gmail\Requests\Labels\CreateLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\DeleteLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\GetLabelRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\ListLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\UpdateLabelRequest;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

/**
 * Gmail Labels Resource - handles label-related API operations.
 *
 * This resource provides methods to manage Gmail labels including
 * listing, creating, updating, and deleting custom labels.
 * System labels (INBOX, SENT, etc.) can be retrieved but not modified.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels
 */
class LabelResource extends BaseResource
{
    /**
     * List all labels in the user's mailbox.
     *
     * Returns both system labels (INBOX, SENT, DRAFT, etc.) and custom user labels.
     * System labels cannot be modified or deleted.
     *
     * @return Response Gmail API response containing array of label objects
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/list
     */
    public function list(): Response
    {
        return $this->connector->send(new ListLabelsRequest);
    }

    /**
     * Get a specific label by ID.
     *
     * @param  string  $id  The label ID to retrieve (e.g., 'INBOX', 'Label_123')
     * @return Response Gmail API response containing label details
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/get
     */
    public function get(string $id): Response
    {
        return $this->connector->send(new GetLabelRequest($id));
    }

    /**
     * Create a new custom label.
     *
     * @param  array  $data  Label data:
     *                       - name: string Label name (required)
     *                       - messageListVisibility: string 'show'|'hide' visibility in message list
     *                       - labelListVisibility: string 'labelShow'|'labelHide' visibility in label list
     *                       - color: array Optional color settings:
     *                       - backgroundColor: string Hex color for background
     *                       - textColor: string Hex color for text
     * @return Response Gmail API response containing created label data
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/create
     */
    public function create(array $data): Response
    {
        return $this->connector->send(new CreateLabelRequest($data));
    }

    /**
     * Update an existing custom label.
     *
     * Only custom labels can be updated. System labels will return an error.
     *
     * @param  string  $id  The label ID to update
     * @param  array  $data  Updated label properties (same format as create)
     * @return Response Gmail API response containing updated label data
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/update
     */
    public function update(string $id, array $data): Response
    {
        return $this->connector->send(new UpdateLabelRequest($id, $data));
    }

    /**
     * Delete a custom label.
     *
     * Only custom labels can be deleted. System labels will return an error.
     * Deleting a label removes it from all messages that had the label applied.
     *
     * @param  string  $id  The label ID to delete
     * @return Response Gmail API response (empty on success)
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/delete
     */
    public function delete(string $id): Response
    {
        return $this->connector->send(new DeleteLabelRequest($id));
    }
}
