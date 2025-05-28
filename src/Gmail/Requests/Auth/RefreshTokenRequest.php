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

    /**
     * Convert array data to form-urlencoded format
     * @param array<string, mixed> $data
     */
    protected function formatFormData(array $data): string
    {
        return http_build_query($data);
    }

    /**
     * Configure request body format
     */
    public function resolveBodyFormatter(): string
    {
        // Tell Saloon to keep the body as-is
        return 'raw';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    public function defaultBody(): string
    {
        $clientId = config('gmail-client.client_id');
        $clientSecret = config('gmail-client.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \RuntimeException('Gmail API client credentials not configured. Check your .env and gmail-client config.');
        }

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ];

        return $this->formatFormData($data);
    }
}
