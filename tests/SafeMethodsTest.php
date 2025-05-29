<?php

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->client = new GmailClient;
    $this->client->authenticate('fake-token');
});

test('safeListLabels returns empty collection when authentication fails', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListLabels();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result)->toBeEmpty();
});

test('safeListLabels returns labels when successful', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([
            'labels' => [
                ['id' => 'INBOX', 'name' => 'INBOX', 'type' => 'system'],
                ['id' => 'STARRED', 'name' => 'STARRED', 'type' => 'system'],
            ],
        ], 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListLabels();

    expect($result)->toHaveCount(2);
});

test('safeListMessages returns empty collection when fails', function () {
    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListMessages();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result)->toBeEmpty();
});

test('safeGetMessage returns null when message not found', function () {
    $mockClient = new MockClient([
        '*users/me/messages/invalid-id*' => MockResponse::make([], 404),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeGetMessage('invalid-id');

    expect($result)->toBeNull();
});

test('isConnected returns true when connection is healthy', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([
            'labels' => [],
        ], 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $connected = $this->client->isConnected();
    expect($connected)->toBeTrue();
});

test('isConnected returns false when connection fails', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $connected = $this->client->isConnected();
    expect($connected)->toBeFalse();
});

test('safe methods handle rate limit errors gracefully', function () {
    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make([
            'error' => [
                'code' => 429,
                'message' => 'Quota exceeded',
            ],
        ], 429),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListMessages();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result)->toBeEmpty();
});

test('safeGetAccountStatistics returns valid data structure on API failures', function () {
    $mockClient = new MockClient([
        '*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeGetAccountStatistics();

    // The method should return a valid structure even when API calls fail
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('partial_failure')
        ->and($result)->toHaveKey('unread_count')
        ->and($result)->toHaveKey('labels_count')
        ->and($result)->toHaveKey('last_updated')
        ->and($result['api_calls_made'])->toBeGreaterThan(0);
});

test('getAccountSummary handles connection failures with safe error messages', function () {
    $mockClient = new MockClient([
        '*' => MockResponse::make([], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->getAccountSummary();

    expect($result['connected'])->toBeFalse()
        ->and($result['errors'])->toBeArray()
        ->and($result['labels_count'])->toBe(0)
        ->and($result['has_unread'])->toBeFalse();
});

test('safeListLabels returns collection on API failures', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListLabels();

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0); // Should return empty collection on failure
});

test('safeListLabels works with lazy option', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListLabels(lazy: true);

    expect($result)->not->toBeNull();
});

test('safeListMessages works with lazy option', function () {
    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListMessages(lazy: true);

    expect($result)->not->toBeNull();
});

test('safeListMessages returns collection on API failures', function () {
    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListMessages();

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0); // Should return empty collection on failure
});

test('getAccountSummary returns basic structure', function () {
    $mockClient = new MockClient([
        // Health check for isConnected()
        '*users/me/labels*' => MockResponse::make([
            'labels' => [
                ['id' => 'INBOX', 'name' => 'INBOX'],
                ['id' => 'SENT', 'name' => 'SENT'],
            ],
        ], 200),
        // All other calls fail
        '*' => MockResponse::make([], 500),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->getAccountSummary();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('connected')
        ->and($result)->toHaveKey('labels_count')
        ->and($result)->toHaveKey('has_unread')
        ->and($result)->toHaveKey('errors');
});
