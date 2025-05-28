<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Labels;

use PartridgeRocks\GmailClient\Gmail\Requests\BaseRequest;
use Saloon\Enums\Method;

/**
 * Gmail Create Label Request - creates a new custom label.
 *
 * This request creates a new user-defined label that can be applied to messages.
 * Custom labels support color customization and visibility settings.
 * System labels (INBOX, SENT, etc.) cannot be created.
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels/create
 */
class CreateLabelRequest extends BaseRequest
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    /** @var array<string, mixed> */
    protected array $labelData;

    /**
     * Create a new label creation request.
     *
     * @param  array<string, mixed>  $data  Label data:
     *                       - name: string Label name (required, must be unique)
     *                       - messageListVisibility: string 'show'|'hide' (default 'show')
     *                       Controls if label appears in message list
     *                       - labelListVisibility: string 'labelShow'|'labelHide' (default 'labelShow')
     *                       Controls if label appears in label list
     *                       - color: array Optional color settings:
     *                       - backgroundColor: string Hex color code (e.g., '#ff0000')
     *                       - textColor: string Hex color code for text
     */
    public function __construct(array $data)
    {
        $this->labelData = $data;
    }

    /**
     * Resolve the API endpoint for this request.
     *
     * @return string Gmail API endpoint for creating labels
     */
    public function resolveEndpoint(): string
    {
        return '/users/me/labels';
    }

    /**
     * Get the request body containing the label data.
     *
     * @return array<string, mixed> Label data to send to Gmail API
     */
    public function defaultBody(): array
    {
        return $this->labelData;
    }
}
