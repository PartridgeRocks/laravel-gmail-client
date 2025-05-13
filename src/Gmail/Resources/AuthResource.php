<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class AuthResource extends BaseResource
{
    /**
     * Exchange an authorization code for an access token.
     *
     * @param  string  $code  The authorization code
     * @param  string|null  $redirectUri  Optional override for the redirect URI
     * @return array The token response data
     *
     * @throws AuthenticationException
     */
    public function exchangeCode(string $code, ?string $redirectUri = null): array
    {
        // We can use the built-in method from AuthorizationCodeGrant trait
        try {
            // Use the configured redirect URI from OAuthConfig if not specified
            if ($redirectUri) {
                $this->connector->oauthConfig()->setRedirectUri($redirectUri);
            }

            $response = $this->connector->getAccessToken($code);
            $tokenData = $response->json();

            // Authenticate the connector with the new token
            $this->connector->authenticate(
                new TokenAuthenticator($tokenData['access_token'])
            );

            return $tokenData;
        } catch (\Exception $e) {
            throw AuthenticationException::fromOAuthError($e->getMessage());
        }
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @param  string  $refreshToken  The refresh token
     * @return array The token response data
     *
     * @throws AuthenticationException
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = $this->connector->refreshAccessToken($refreshToken);
            $tokenData = $response->json();

            // Authenticate the connector with the new token
            $this->connector->authenticate(
                new TokenAuthenticator($tokenData['access_token'])
            );

            return $tokenData;
        } catch (\Exception $e) {
            throw AuthenticationException::fromOAuthError($e->getMessage());
        }
    }

    /**
     * Get the authorization URL.
     *
     * @param  string|null  $redirectUri  Optional override for the redirect URI
     * @param  array  $scopes  Optional override for scopes
     * @param  array  $additionalParams  Additional query parameters
     * @return string The authorization URL
     *
     * @throws \RuntimeException
     */
    public function getAuthorizationUrl(
        ?string $redirectUri = null,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        // Use the configured redirect URI from OAuthConfig if not specified
        if ($redirectUri) {
            $this->connector->oauthConfig()->setRedirectUri($redirectUri);
        }

        // Use the configured scopes from OAuthConfig if not specified
        if (! empty($scopes)) {
            $this->connector->oauthConfig()->setDefaultScopes($scopes);
        }

        return $this->connector->getAuthorizationUrl($additionalParams);
    }
}
