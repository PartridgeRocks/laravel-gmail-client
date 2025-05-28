<?php

namespace PartridgeRocks\GmailClient\Contracts;

use DateTimeInterface;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;

interface AuthServiceInterface
{
    /**
     * Authenticate with a token.
     *
     * @throws AuthenticationException
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?DateTimeInterface $expiresAt = null
    ): void;

    /**
     * Get the authorization URL for the OAuth flow.
     *
     * @param  array<string>  $scopes
     * @param  array<string, mixed>  $additionalParams
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string;

    /**
     * Exchange an authorization code for an access token.
     *
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code, string $redirectUri): array;

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken): array;
}
