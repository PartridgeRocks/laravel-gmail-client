<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

/**
 * Composite interface for OAuth authentication flows.
 *
 * This interface handles the complete OAuth 2.0 flow including authorization
 * URL generation, code exchange, and token refresh operations.
 */
interface GmailOAuthInterface
{
    /**
     * Get the authorization URL for OAuth flow.
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
     * Exchange authorization code for access token.
     *
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code, string $redirectUri): array;

    /**
     * Refresh an access token using refresh token.
     *
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken): array;
}