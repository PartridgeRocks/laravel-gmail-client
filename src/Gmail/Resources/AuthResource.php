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
                $clientId = config('gmail-client.client_id');
                $clientSecret = config('gmail-client.client_secret');

                if (empty($clientId) || empty($clientSecret)) {
                    throw new \RuntimeException('Gmail API client credentials not configured. Check your .env and gmail-client config.');
                }

                return [
                    'code' => $this->code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
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
                $clientId = config('gmail-client.client_id');
                $clientSecret = config('gmail-client.client_secret');

                if (empty($clientId) || empty($clientSecret)) {
                    throw new \RuntimeException('Gmail API client credentials not configured. Check your .env and gmail-client config.');
                }

                return [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ];
            }
        });
    }

    /**
     * Get the authorization URL.
     *
     * @throws \RuntimeException
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        $clientId = config('gmail-client.client_id');

        if (empty($clientId)) {
            throw new \RuntimeException('Gmail API client_id not configured. Check your .env and gmail-client config.');
        }

        $scopes = ! empty($scopes) ? $scopes : config('gmail-client.scopes');

        $params = array_merge([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ], $additionalParams);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }
}
