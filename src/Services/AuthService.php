<?php

namespace PartridgeRocks\GmailClient\Services;

use DateTimeInterface;
use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\GmailOAuthAuthenticator;
use PartridgeRocks\GmailClient\Gmail\Resources\AuthResource;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private GmailConnector $connector
    ) {}

    /**
     * Authenticate with a token.
     *
     * @throws AuthenticationException
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
     * Get the authentication resource.
     */
    private function getAuthResource(): AuthResource
    {
        return new AuthResource($this->connector);
    }
}
