<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Http\BaseResource;
use Saloon\Http\Response;
use PartridgeRocks\GmailClient\Gmail\Requests\Auth\ExchangeCodeRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Auth\RefreshTokenRequest;

class AuthResource extends BaseResource
{
    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode(string $code, string $redirectUri): Response
    {
        return $this->connector->send(new ExchangeCodeRequest($code, $redirectUri));
    }

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): Response
    {
        return $this->connector->send(new RefreshTokenRequest($refreshToken));
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