<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests;

use PartridgeRocks\GmailClient\Data\Errors\ErrorDTO;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\GmailClientException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use Saloon\Http\Request;
use Saloon\Http\Response;

abstract class BaseRequest extends Request
{
    /**
     * Process the response and throw appropriate exceptions for errors.
     *
     *
     * @throws AuthenticationException
     * @throws NotFoundException
     * @throws RateLimitException
     * @throws ValidationException
     * @throws GmailClientException
     */
    public function processResponse(Response $response): Response
    {
        $status = $response->status();

        // Only process error responses
        if ($status >= 200 && $status < 300) {
            return $response;
        }

        // Extract error data for better context
        try {
            $errorData = $response->json();
        } catch (\JsonException) {
            $errorData = [];
        }

        // Handle specific error types based on status code and response content
        switch ($status) {
            case 400:
                throw ValidationException::fromValidationResponse($errorData);
            case 401:
                throw AuthenticationException::fromResponse($errorData);
            case 404:
                // Extract resource identifier from the request URL
                $path = $response->getRequest()->resolveEndpoint();
                $resourceId = $this->extractResourceId($path);

                throw NotFoundException::fromPath($path, $resourceId);
            case 429:
                $retryAfter = (int) ($response->header('Retry-After') ?? '0');

                throw RateLimitException::quotaExceeded($retryAfter);
            default:
                $error = ErrorDTO::fromResponse($errorData);

                throw new GmailClientException(
                    "Gmail API Error: {$error->message}",
                    $status,
                    null,
                    $error
                );
        }
    }

    /**
     * Extract a resource ID from a path
     */
    protected function extractResourceId(string $path): ?string
    {
        // Simple extraction of the last path segment
        $segments = explode('/', trim($path, '/'));

        return end($segments);
    }
}
