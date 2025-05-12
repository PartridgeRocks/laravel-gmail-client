<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Auth;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RefreshTokenRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected string $refreshToken;

    public function __construct(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function resolveEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
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
}
