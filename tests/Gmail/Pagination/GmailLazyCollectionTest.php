<?php

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('loads messages lazily with small dataset', function () {

    // Create mock responses
    $messagesListJson = [
        'messages' => [
            ['id' => 'msg1', 'threadId' => 'thread1'],
            ['id' => 'msg2', 'threadId' => 'thread2'],
        ],
        'nextPageToken' => 'page2token',
    ];

    $message1Json = [
        'id' => 'msg1',
        'threadId' => 'thread1',
        'labelIds' => ['INBOX'],
        'snippet' => 'Test message 1',
        'payload' => [
            'headers' => [
                ['name' => 'Subject', 'value' => 'Test Subject 1'],
                ['name' => 'From', 'value' => 'sender1@example.com'],
            ],
        ],
        'sizeEstimate' => 1000,
        'internalDate' => '1624982400000',
    ];

    $message2Json = [
        'id' => 'msg2',
        'threadId' => 'thread2',
        'labelIds' => ['INBOX'],
        'snippet' => 'Test message 2',
        'payload' => [
            'headers' => [
                ['name' => 'Subject', 'value' => 'Test Subject 2'],
                ['name' => 'From', 'value' => 'sender2@example.com'],
            ],
        ],
        'sizeEstimate' => 2000,
        'internalDate' => '1624982500000',
    ];

    // Set up mock client
    $mockClient = new MockClient([
        '*users/me/messages' => MockResponse::make($messagesListJson, 200),
        '*users/me/messages/msg1*' => MockResponse::make($message1Json, 200),
        '*users/me/messages/msg2*' => MockResponse::make($message2Json, 200),
    ]);

    // Create client and set up mock
    $client = new GmailClient;
    $client->getConnector()->withMockClient($mockClient);
    $client->authenticate('test-token');

    // Use lazy collection with small dataset
    $lazyCollection = $client->listMessages(lazy: true, maxResults: 2);

    // Process only the first 2 items to avoid memory issues
    $processed = $lazyCollection
        ->take(2)
        ->filter(fn ($email) => $email instanceof Email)
        ->map(fn ($email) => $email->id)
        ->toArray();

    expect($processed)->toBe(['msg1', 'msg2']);
});

it('converts to standard collection with single item', function () {

    $mockClient = new MockClient([
        '*users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg1', 'threadId' => 'thread1'],
            ],
        ], 200),
        '*users/me/messages/msg1*' => MockResponse::make([
            'id' => 'msg1',
            'threadId' => 'thread1',
            'labelIds' => ['INBOX'],
            'snippet' => 'Test message',
            'payload' => [
                'headers' => [
                    ['name' => 'Subject', 'value' => 'Test Subject'],
                    ['name' => 'From', 'value' => 'sender@example.com'],
                ],
            ],
            'sizeEstimate' => 1000,
            'internalDate' => '1624982400000',
        ], 200),
    ]);

    $client = new GmailClient;
    $client->getConnector()->withMockClient($mockClient);
    $client->authenticate('test-token');

    // Test with single item to avoid memory issues
    $lazy = $client->listMessages(lazy: true, maxResults: 1);
    $regular = $lazy->toCollection();

    expect($regular)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($regular->first())->toBeInstanceOf(Email::class);
});
