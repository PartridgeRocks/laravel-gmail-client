<?php

namespace PartridgeRocks\GmailClient\Gmail\Requests\Auth;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ExchangeCodeRequest extends Request
{
    // Define the HTTP method
    protected Method $method = Method::POST;

    protected string $code;
    protected string $redirectUri;

    public function __construct(string $code, string $redirectUri) {
        $this->code = $code;
        $this->redirectUri = $redirectUri;
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
            'code' => $this->code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];
    }
}