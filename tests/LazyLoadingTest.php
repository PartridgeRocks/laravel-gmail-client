<?php

use PartridgeRocks\GmailClient\Gmail\Pagination\GmailLazyCollection;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->client = new GmailClient;
    $this->client->authenticate('test-token');
});

it('can create lazy collection for messages without errors', function () {
    $messagesJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);

    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make($messagesJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $lazyCollection = $this->client->lazyLoadMessages();

    expect($lazyCollection)->toBeInstanceOf(GmailLazyCollection::class);
});

it('can create lazy collection for labels without errors', function () {
    $labelsJson = json_decode(file_get_contents(__DIR__.'/fixtures/labels-list.json'), true);

    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make($labelsJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $lazyCollection = $this->client->lazyLoadLabels();

    expect($lazyCollection)->toBeInstanceOf(GmailLazyCollection::class);
});

it('safely handles lazy loading failures for messages', function () {
    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make([
            'error' => ['code' => 401, 'message' => 'Invalid credentials'],
        ], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListMessages([], false, 100, true);

    // Should return empty GmailLazyCollection instead of throwing
    expect($result)->toBeInstanceOf(GmailLazyCollection::class);
});

it('safely handles lazy loading failures for labels', function () {
    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make([
            'error' => ['code' => 401, 'message' => 'Invalid credentials'],
        ], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->safeListLabels(true); // lazy=true

    // Should return empty GmailLazyCollection instead of throwing
    expect($result)->toBeInstanceOf(GmailLazyCollection::class);
});

it('returns proper lazy collection when lazy option is used in listMessages', function () {
    $messagesJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);

    $mockClient = new MockClient([
        '*users/me/messages*' => MockResponse::make($messagesJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->listMessages([], false, 100, true);

    expect($result)->toBeInstanceOf(GmailLazyCollection::class);
});

it('returns proper lazy collection when lazy option is used in listLabels', function () {
    $labelsJson = json_decode(file_get_contents(__DIR__.'/fixtures/labels-list.json'), true);

    $mockClient = new MockClient([
        '*users/me/labels*' => MockResponse::make($labelsJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->listLabels(false, true);

    expect($result)->toBeInstanceOf(GmailLazyCollection::class);
});
