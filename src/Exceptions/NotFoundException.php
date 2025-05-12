<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use PartridgeRocks\GmailClient\Data\Errors\NotFoundErrorDTO;

class NotFoundException extends GmailClientException
{
    public static function message(string $id): self
    {
        $error = NotFoundErrorDTO::forResource('message', $id);
        return new static("Email message with ID '{$id}' not found.", 404, null, $error);
    }

    public static function label(string $id): self
    {
        $error = NotFoundErrorDTO::forResource('label', $id);
        return new static("Label with ID '{$id}' not found.", 404, null, $error);
    }

    public static function thread(string $id): self
    {
        $error = NotFoundErrorDTO::forResource('thread', $id);
        return new static("Thread with ID '{$id}' not found.", 404, null, $error);
    }

    /**
     * Create from a 404 response
     */
    public static function fromResponse(array $response, string $resourceType, string $resourceId): self
    {
        $errorData = $response['error'] ?? $response;
        $message = $errorData['message'] ?? "{$resourceType} not found";
        
        $error = NotFoundErrorDTO::forResource(
            $resourceType,
            $resourceId,
            $message,
            $response
        );
        
        return new static($message, 404, null, $error);
    }
}