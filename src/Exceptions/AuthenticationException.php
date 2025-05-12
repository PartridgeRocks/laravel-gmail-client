<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use PartridgeRocks\GmailClient\Data\Errors\AuthenticationErrorDTO;
use PartridgeRocks\GmailClient\Data\Errors\ErrorDTO;

class AuthenticationException extends GmailClientException
{
    public static function missingToken(): self
    {
        $error = AuthenticationErrorDTO::fromType('missing_token');
        return new static($error->message, 401, null, $error);
    }

    public static function invalidToken(): self
    {
        $error = AuthenticationErrorDTO::fromType('invalid_token');
        return new static($error->message, 401, null, $error);
    }

    public static function tokenExpired(): self
    {
        $error = AuthenticationErrorDTO::fromType('token_expired');
        return new static($error->message, 401, null, $error);
    }

    public static function refreshFailed(?string $detail = null): self
    {
        $error = AuthenticationErrorDTO::fromType('refresh_failed', $detail);
        return new static($error->message, 401, null, $error);
    }

    public static function missingCredentials(): self
    {
        $error = AuthenticationErrorDTO::fromType(
            'unauthorized', 
            'Missing required OAuth credentials. Please check your configuration.'
        );
        return new static($error->message, 401, null, $error);
    }

    public static function authorizationFailed(?string $message = null): self
    {
        $error = AuthenticationErrorDTO::fromType(
            'unauthorized',
            $message ?? 'Unknown error'
        );
        return new static('Gmail authorization failed: ' . ($message ?? 'Unknown error'), 401, null, $error);
    }

    /**
     * Create from a 401 response
     */
    public static function fromResponse(array $response, ?string $message = null): self
    {
        $errorData = $response['error'] ?? $response;
        $type = 'unauthorized';
        
        // Try to determine more specific type
        if (isset($errorData['error_description'])) {
            if (strpos($errorData['error_description'], 'expired') !== false) {
                $type = 'token_expired';
            } elseif (strpos($errorData['error_description'], 'invalid') !== false) {
                $type = 'invalid_token';
            }
        }
        
        $error = AuthenticationErrorDTO::fromType(
            $type,
            $message ?? ($errorData['error_description'] ?? $errorData['message'] ?? 'Authentication error'),
            $response
        );
        
        return new static($error->message, 401, null, $error);
    }
}