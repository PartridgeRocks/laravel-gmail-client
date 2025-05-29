<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;

/**
 * Composite interface for write/modify Gmail operations.
 *
 * This interface combines all write operations (send messages, modify labels,
 * authentication) into a single contract for components that need to modify
 * Gmail state.
 */
interface GmailWriteInterface
{
    /**
     * Authenticate with Gmail using an access token.
     *
     * @return $this
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeInterface $expiresAt = null
    ): self;

    /**
     * Send an email message.
     */
    /**
     * @param  array<string, string>  $headers
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $body,
        ?string $from = null,
        array $headers = []
    ): Email;

    /**
     * Add labels to a message.
     */
    /**
     * @param  array<string>  $labelIds
     */
    public function addLabelsToMessage(string $messageId, array $labelIds): Email;

    /**
     * Remove labels from a message.
     */
    /**
     * @param  array<string>  $labelIds
     */
    public function removeLabelsFromMessage(string $messageId, array $labelIds): Email;

    /**
     * Modify message labels (add and remove in single operation).
     */
    /**
     * @param  array<string>  $addLabelIds
     * @param  array<string>  $removeLabelIds
     */
    public function modifyMessageLabels(
        string $messageId,
        array $addLabelIds = [],
        array $removeLabelIds = []
    ): Email;

    /**
     * Create a new label.
     */
    /**
     * @param  array<string, mixed>  $options
     */
    public function createLabel(string $name, array $options = []): Label;

    /**
     * Update an existing label.
     */
    /**
     * @param  array<string, mixed>  $updates
     */
    public function updateLabel(string $labelId, array $updates): Label;

    /**
     * Delete a label.
     */
    public function deleteLabel(string $labelId): bool;
}
