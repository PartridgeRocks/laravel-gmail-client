<?php

namespace PartridgeRocks\GmailClient\Tests\TestHelpers;

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class MockClientAdapter
{
    /**
     * Create a mock client for testing.
     */
    public static function create(array $responses = []): MockClient
    {
        return new MockClient($responses);
    }

    /**
     * Create a mock response with JSON data.
     */
    public static function mockJsonResponse(array $data, int $status = 200, array $headers = []): MockResponse
    {
        return MockResponse::make($data, $status, $headers);
    }
}
