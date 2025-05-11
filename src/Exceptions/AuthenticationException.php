<?php

namespace PartridgeRocks\GmailClient\Exceptions;

class AuthenticationException extends GmailClientException
{
    public static function missingToken(): self
    {
        return new static('No access token provided. You must authenticate with the Gmail API first.');
    }

    public static function invalidToken(): self
    {
        return new static('The provided access token is invalid or has expired.');
    }

    public static function missingCredentials(): self
    {
        return new static('Missing required OAuth credentials. Please check your configuration.');
    }

    public static function authorizationFailed(string $message = null): self
    {
        return new static('Gmail authorization failed: ' . ($message ?? 'Unknown error'));
    }
}