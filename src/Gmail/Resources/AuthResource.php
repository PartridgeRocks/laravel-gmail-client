<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Enums\Method;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;

class AuthResource extends BaseResource
{
    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode(string $code, string $redirectUri): Response
    {
        return $this->connector->send(new class($code, $redirectUri) extends Request
        {
            public function __construct(protected string $code, protected string $redirectUri) {}

            public function resolveEndpoint(): string
            {
                return 'https://oauth2.googleapis.com/token';
            }

            public function method(): Method
            {
                return Method::POST;
            }

            protected function defaultHeaders(): array
            {
                return [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ];
            }

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

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): Response
    {
        return $this->connector->send(new class($refreshToken) extends Request
        {
            public function __construct(protected string $refreshToken) {}

            public function resolveEndpoint(): string
            {
                return 'https://oauth2.googleapis.com/token';
            }

            public function method(): Method
            {
                return Method::POST;
            }

            protected function defaultHeaders(): array
            {
                return [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ];
            }

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

    /**
     * Get the authorization URL.
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
