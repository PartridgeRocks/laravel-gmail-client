<?php

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
