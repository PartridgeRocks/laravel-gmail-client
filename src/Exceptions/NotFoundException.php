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
    public static function fromNotFoundResponse(array $response, string $resourceType, string $resourceId): self
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

    /**
     * Create an exception from a response array
     *
     * @param  array  $response  The response data
     * @param  string|null  $message  Optional custom message
     */
    public static function fromResponse(array $response, ?string $message = null): self
    {
        $error = NotFoundErrorDTO::fromResponse($response);

        return new static(
            $message ?? $error->message,
            404,
            null,
            $error
        );
    }

    /**
     * Create a not found exception from a URL path
     *
     * @param  string  $path  The URL path
     * @param  string|null  $resourceId  The resource ID if available
     */
    public static function fromPath(string $path, ?string $resourceId = null): self
    {
        // Determine resource type from path
        $resourceType = 'resource';

        if (strpos($path, 'messages') !== false) {
            $resourceType = 'message';
        } elseif (strpos($path, 'labels') !== false) {
            $resourceType = 'label';
        } elseif (strpos($path, 'threads') !== false) {
            $resourceType = 'thread';
        }

        $error = NotFoundErrorDTO::forResource(
            $resourceType,
            $resourceId ?? 'unknown',
            "$resourceType not found",
            ['path' => $path]
        );

        return new static("$resourceType not found", 404, null, $error);
    }
}
