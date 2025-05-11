<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Enums\Method;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;

class AuthResource extends BaseResource
{
    /****
     * Exchanges an OAuth2 authorization code for an access token using Google's token endpoint.
     *
     * @param string $code The authorization code received from the OAuth2 consent screen.
     * @param string $redirectUri The redirect URI used in the OAuth2 flow.
     * @return Response The HTTP response containing the access token and related data.
     */
    public function exchangeCode(string $code, string $redirectUri): Response
    {
        return $this->connector->send(new class($code, $redirectUri) extends Request
        {
            /****
 * Initializes the request with the authorization code and redirect URI for token exchange.
 *
 * @param string $code The authorization code received from the OAuth2 authorization server.
 * @param string $redirectUri The URI to which the authorization server redirected the user.
 */
            public function __construct(protected string $code, protected string $redirectUri) {}

            /**
             * Returns the OAuth2 token endpoint URL for Google authentication.
             *
             * @return string The URL of the Google OAuth2 token endpoint.
             */
            public function resolveEndpoint(): string
            {
                return 'https://oauth2.googleapis.com/token';
            }

            /**
             * Returns the HTTP method used for the request.
             *
             * @return Method The HTTP POST method.
             */
            public function method(): Method
            {
                return Method::POST;
            }

            /**
             * Returns the default HTTP headers for requests, setting the content type to URL-encoded form data.
             *
             * @return array Associative array of default headers.
             */
            protected function defaultHeaders(): array
            {
                return [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ];
            }

            /**
             * Returns the default request body for exchanging an authorization code for an access token.
             *
             * @return array Associative array containing the authorization code, client credentials, redirect URI, and grant type.
             */
            public function defaultBody(): array
            {
                return [
                    'code' => $this->code,
                    'client_id' => config('gmail-client.client_id'),
                    'client_secret' => config('gmail-client.client_secret'),
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                ];
            }
        });
    }

    /****
     * Exchanges a refresh token for a new access token by sending a POST request to Google's OAuth2 token endpoint.
     *
     * @param string $refreshToken The refresh token obtained during initial authorization.
     * @return Response The HTTP response from the token endpoint containing the new access token and related data.
     */
    public function refreshToken(string $refreshToken): Response
    {
        return $this->connector->send(new class($refreshToken) extends Request
        {
            /**
             * Initializes the AuthResource with a refresh token.
             *
             * @param  string  $refreshToken  The OAuth2 refresh token to use for authentication operations.
             */
            public function __construct(protected string $refreshToken) {}

            /**
             * Returns the Google OAuth2 token endpoint URL.
             *
             * @return string The URL for exchanging authorization codes or refreshing tokens.
             */
            public function resolveEndpoint(): string
            {
                return 'https://oauth2.googleapis.com/token';
            }

            /**
             * Returns the HTTP method used for the request.
             *
             * @return Method The HTTP POST method.
             */
            public function method(): Method
            {
                return Method::POST;
            }

            /**
             * Returns the default HTTP headers for requests, setting the content type to URL-encoded form data.
             *
             * @return array Associative array of default headers.
             */
            protected function defaultHeaders(): array
            {
                return [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ];
            }

            /**
             * Returns the default request body for refreshing an OAuth2 access token.
             *
             * @return array Associative array containing client credentials, refresh token, and grant type.
             */
            public function defaultBody(): array
            {
                return [
                    'client_id' => config('gmail-client.client_id'),
                    'client_secret' => config('gmail-client.client_secret'),
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ];
            }
        });
    }

    /****
     * Constructs the Google OAuth2 authorization URL for user consent.
     *
     * Generates a URL for initiating the OAuth2 authorization flow, using the provided redirect URI, optional scopes, and any additional query parameters. If no scopes are specified, defaults to the configured scopes.
     *
     * @param string $redirectUri The URI to redirect to after user authorization.
     * @param array $scopes Optional list of OAuth2 scopes; defaults to configuration if empty.
     * @param array $additionalParams Optional additional query parameters to include in the URL.
     * @return string The complete Google OAuth2 authorization URL.
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        $scopes = ! empty($scopes) ? $scopes : config('gmail-client.scopes');

        $params = array_merge([
            'client_id' => config('gmail-client.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ], $additionalParams);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }
}
