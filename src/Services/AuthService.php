<?php

namespace PartridgeRocks\GmailClient\Services;

use DateTimeInterface;
use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Gmail\ExceptionHandling;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\GmailOAuthAuthenticator;
use PartridgeRocks\GmailClient\Gmail\Resources\AuthResource;

class AuthService implements AuthServiceInterface
{
    use ExceptionHandling;

    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * Authenticate with a token.
     *
     * @param  string  $accessToken  The OAuth access token
     * @param  string|null  $refreshToken  The OAuth refresh token (optional)
     * @param  DateTimeInterface|null  $expiresAt  Token expiration time (optional)
     *
     * @throws AuthenticationException When token is missing or invalid
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?DateTimeInterface $expiresAt = null
    ): void {
        if (empty($accessToken)) {
            throw AuthenticationException::missingToken();
        }

        $authenticator = new GmailOAuthAuthenticator($accessToken, $refreshToken, 'Bearer', $expiresAt);
        $this->connector->authenticate($authenticator);
    }

    /**
     * Get the authorization URL for the OAuth flow.
     *
     * @param  string  $redirectUri  The redirect URI for OAuth callback
     * @param  array  $scopes  Array of OAuth scopes to request
     * @param  array  $additionalParams  Additional OAuth parameters
     * @return string The authorization URL
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        return $this->getAuthResource()->getAuthorizationUrl($redirectUri, $scopes, $additionalParams);
    }

    /**
     * Exchange an authorization code for an access token.
     *
     * @param  string  $code  The authorization code from OAuth callback
     * @param  string  $redirectUri  The same redirect URI used for authorization
     * @return array Token response including access_token, refresh_token, expires_in
     *
     * @throws AuthenticationException When code exchange fails
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = $this->getAuthResource()->exchangeCode($code, $redirectUri);
        /** @var array $data */
        $data = $response->json();

        // Auto-authenticate with the new token
        if (isset($data['access_token'])) {
            $expiresAt = isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])
                : null;

            $this->authenticate(
                $data['access_token'],
                $data['refresh_token'] ?? null,
                $expiresAt
            );
        }

        return $data;
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @param  string  $refreshToken  The refresh token to use
     * @return array New token response including access_token, expires_in
     *
     * @throws AuthenticationException When token refresh fails
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->getAuthResource()->refreshToken($refreshToken);
        /** @var array $data */
        $data = $response->json();

        // Auto-authenticate with the new token
        if (isset($data['access_token'])) {
            $expiresAt = isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])
                : null;

            $this->authenticate(
                $data['access_token'],
                $refreshToken, // Keep the same refresh token if not provided
                $expiresAt
            );
        }

        return $data;
    }

    /**
     * Parse the Retry-After header value.
     *
     * @param  string  $value  The Retry-After header value
     * @return int Number of seconds to wait before retrying
     */
    protected function parseRetryAfterHeader(string $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return max(0, $timestamp - time());
        }

        return 60; // Default retry after 60 seconds for auth operations
    }

    /**
     * Get the authentication resource.
     *
     * @return AuthResource The authentication resource instance
     */
    private function getAuthResource(): AuthResource
    {
        return new AuthResource($this->connector);
    }
}
