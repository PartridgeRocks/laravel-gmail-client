<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Tests\Integration;

use PartridgeRocks\GmailClient\Builders\GmailClientBuilder;
use PartridgeRocks\GmailClient\GmailClient;
use PartridgeRocks\GmailClient\Tests\TestCase;
use Saloon\Http\Faking\MockClient;

abstract class GmailIntegrationTestCase extends TestCase
{
    protected MockClient $mockClient;
    protected GmailClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = new MockClient();
        $this->client = $this->createGmailClient();
    }

    protected function createGmailClient(string $token = 'test-token'): GmailClient
    {
        return GmailClientBuilder::create()
            ->withToken($token)
            ->buildWithDefaults();
    }

    protected function setMockClient(MockClient $mockClient): void
    {
        $this->mockClient = $mockClient;
        
        // Update the client's connector to use the mock
        $connector = $this->client->getConnector();
        $connector->sender()->setClient($mockClient);
    }

    protected function expectApiCall(string $pattern, array $response, int $status = 200): void
    {
        $this->mockClient->addResponse($pattern, $response, $status);
    }

    protected function expectAuthenticationSuccess(string $accessToken = 'test-access-token'): void
    {
        $this->expectApiCall('oauth2.googleapis.com/token', [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'test-refresh-token',
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly'
        ]);
    }

    protected function expectAuthenticationFailure(string $error = 'invalid_grant'): void
    {
        $this->expectApiCall('oauth2.googleapis.com/token', [
            'error' => $error,
            'error_description' => 'Invalid authorization code.'
        ], 400);
    }

    protected function expectRateLimitError(): void
    {
        $this->expectApiCall('*', [
            'error' => [
                'code' => 429,
                'message' => 'Rate limit exceeded',
                'status' => 'RESOURCE_EXHAUSTED'
            ]
        ], 429);
    }

    protected function expectNotFoundError(): void
    {
        $this->expectApiCall('*', [
            'error' => [
                'code' => 404,
                'message' => 'Requested entity was not found.',
                'status' => 'NOT_FOUND'
            ]
        ], 404);
    }

    protected function expectUnauthorizedError(): void
    {
        $this->expectApiCall('*', [
            'error' => [
                'code' => 401,
                'message' => 'Request had invalid authentication credentials.',
                'status' => 'UNAUTHENTICATED'
            ]
        ], 401);
    }

    protected function assertApiCallMade(string $url): void
    {
        $this->assertTrue(
            $this->mockClient->hasRequestMatching($url),
            "Expected API call to {$url} was not made"
        );
    }

    protected function assertApiCallNotMade(string $url): void
    {
        $this->assertFalse(
            $this->mockClient->hasRequestMatching($url),
            "Unexpected API call to {$url} was made"
        );
    }
}