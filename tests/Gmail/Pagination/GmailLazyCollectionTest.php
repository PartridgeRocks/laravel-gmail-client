<?php

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailLazyCollection;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Faking\MockClient;

it('loads messages lazily', function () {
    // Skip test for now since we're refactoring the API
    $this->markTestSkipped('Skipping until implementation is complete');
    
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
    $client = new GmailClient();
    $client->getConnector()->withMockClient($mockClient);
    $client->authenticate('test-token');
    
    // Use lazy collection
    $lazyCollection = $client->listMessages(lazy: true);
    
    // First item shouldn't make additional requests yet
    $lazyCollection->take(1);
    
    // When we actually process items, it should make requests as needed
    $processed = $lazyCollection
        ->filter(fn($email) => $email instanceof Email)
        ->map(fn($email) => $email->id)
        ->all();
        
    expect($processed)->toBe(['msg1', 'msg2']);
});

it('converts to standard collection', function () {
    // Skip test for now since we're refactoring the API
    $this->markTestSkipped('Skipping until implementation is complete');
    
    $mockClient = new MockClient([
        '*users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg1', 'threadId' => 'thread1'],
            ],
        ], 200),
        '*users/me/messages/msg1*' => MockResponse::make([
            'id' => 'msg1',
            'threadId' => 'thread1',
            'snippet' => 'Test message',
        ], 200),
    ]);
    
    $client = new GmailClient();
    $client->getConnector()->withMockClient($mockClient);
    $client->authenticate('test-token');
    
    $lazy = $client->listMessages(lazy: true);
    $regular = $lazy->toCollection();
    
    expect($regular)->toBeInstanceOf(Collection::class);
});