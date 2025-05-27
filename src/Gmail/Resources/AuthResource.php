<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

/**
 * Gmail Authentication Resource - handles OAuth2 authentication flows.
 *
 * This resource manages the OAuth2 authentication process for Gmail API access,
 * including authorization URL generation, code exchange, and token refresh operations.
 * It implements the standard OAuth2 authorization code flow.
 *
 * @see https://developers.google.com/gmail/api/auth/about-auth
 * @see https://developers.google.com/identity/protocols/oauth2
 */
class AuthResource extends BaseResource
{
    /**
     * Exchange an authorization code for an access token.
     *
     * This method completes the OAuth2 authorization code flow by exchanging
     * the authorization code received from the authorization server for an access token.
     *
     * @param  string  $code  The authorization code from OAuth callback
     * @param  string|null  $redirectUri  Override for redirect URI (must match authorization request)
     * @return array Token response containing:
     *               - access_token: string The access token for API requests
     *               - refresh_token: string Token for refreshing access when expired
     *               - expires_in: int Token lifetime in seconds
     *               - token_type: string Usually 'Bearer'
     *               - scope: string Granted OAuth scopes
     *
     * @throws AuthenticationException When code exchange fails or token is invalid
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server#exchange-authorization-code
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
     * When an access token expires, use this method to obtain a new access token
     * without requiring user re-authorization. The refresh token is long-lived
     * and can be used multiple times.
     *
     * @param  string  $refreshToken  The refresh token from initial authorization
     * @return array New token response containing:
     *               - access_token: string New access token for API requests
     *               - expires_in: int New token lifetime in seconds
     *               - token_type: string Usually 'Bearer'
     *               - scope: string Granted OAuth scopes
     *
     * @throws AuthenticationException When refresh fails or refresh token is invalid
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server#refresh
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
     * Get the authorization URL for OAuth2 flow.
     *
     * Generates the URL where users should be redirected to grant permissions.
     * Users will authenticate with Google and authorize your application to access their Gmail.
     *
     * @param  string|null  $redirectUri  Override for redirect URI (where user returns after auth)
     * @param  array  $scopes  Override for OAuth scopes (e.g., ['https://www.googleapis.com/auth/gmail.readonly'])
     * @param  array  $additionalParams  Additional OAuth parameters:
     *                                   - state: string CSRF protection token
     *                                   - access_type: string 'offline' for refresh tokens
     *                                   - prompt: string 'consent' to force consent screen
     *                                   - login_hint: string Email address hint for user
     * @return string Complete authorization URL for user redirection
     *
     * @throws \RuntimeException When OAuth configuration is invalid
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server#creatingclient
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
