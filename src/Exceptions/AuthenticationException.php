<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use PartridgeRocks\GmailClient\Data\Errors\AuthenticationErrorDTO;

class AuthenticationException extends GmailClientException
{
    public static function missingToken(?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::MISSING_TOKEN);

        return new static($error->message, 401, $previous, $error);
    }

    public static function invalidToken(?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::INVALID_TOKEN);

        return new static($error->message, 401, $previous, $error);
    }

    public static function tokenExpired(?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::TOKEN_EXPIRED);

        return new static($error->message, 401, $previous, $error);
    }

    public static function refreshFailed(?string $detail = null, ?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::REFRESH_FAILED, $detail);

        return new static($error->message, 401, $previous, $error);
    }

    public static function missingCredentials(?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::UNAUTHORIZED,
            'Missing required OAuth credentials. Please check your configuration.'
        );

        return new static($error->message, 401, $previous, $error);
    }

    public static function authorizationFailed(?string $message = null, ?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::UNAUTHORIZED,
            $message ?? 'Unknown error'
        );

        return new static('Gmail authorization failed: '.($message ?? 'Unknown error'), 401, $previous, $error);
    }

    /**
     * Create from OAuth authentication errors
     */
    public static function fromOAuthError(string $message, ?\Throwable $previous = null): self
    {
        $error = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::OAUTH_ERROR,
            $message,
            ['oauth_flow' => true, 'timestamp' => now()]
        );

        return new static("OAuth authentication failed: {$message}", 401, $previous, $error);
    }

    /**
     * Create from a 401 response
     */
    public static function fromResponse(array $response, ?string $message = null, ?\Throwable $previous = null): self
    {
        $errorData = $response['error'] ?? $response;
        $type = AuthenticationErrorDTO::UNAUTHORIZED;

        // Try to determine more specific type based on error description
        if (isset($errorData['error_description'])) {
            $description = strtolower($errorData['error_description']);
            if (str_contains($description, 'expired')) {
                $type = AuthenticationErrorDTO::TOKEN_EXPIRED;
            } elseif (str_contains($description, 'invalid')) {
                $type = AuthenticationErrorDTO::INVALID_TOKEN;
            }
        }

        $error = AuthenticationErrorDTO::fromType(
            $type,
            $message ?? ($errorData['error_description'] ?? $errorData['message'] ?? 'Authentication error'),
            $response
        );

        return new static($error->message, 401, $previous, $error);
    }
}
