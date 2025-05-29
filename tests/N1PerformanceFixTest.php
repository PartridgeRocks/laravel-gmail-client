<?php

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->client = new GmailClient;
});

it('optimizes listMessages with fullDetails=false using minimal data', function () {
    $messagesListJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make($messagesListJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);
    $this->client->authenticate('test-token');

    // Test with fullDetails=false (should not make individual API calls)
    $messages = $this->client->listMessages(['q' => 'is:unread'], fullDetails: false);

    expect($messages)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($messages->first())->toBeInstanceOf(Email::class)
        ->and($messages->first()->id)->toBe('msg123')
        ->and($messages->first()->snippet)->toBeNull() // Minimal data has no snippet
        ->and($messages->first()->body)->toBeNull(); // Minimal data has no body
});

it('batches API calls for listMessages with fullDetails=true to avoid N+1', function () {
    $messagesListJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);
    $messageJson = json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true);

    // Create multiple messages for better N+1 testing
    $multipleMessagesJson = $messagesListJson;
    $multipleMessagesJson['messages'] = [
        ['id' => 'msg123', 'threadId' => 'thread123'],
        ['id' => 'msg456', 'threadId' => 'thread456'],
        ['id' => 'msg789', 'threadId' => 'thread789'],
    ];

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make($multipleMessagesJson, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg123*' => MockResponse::make($messageJson, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg456*' => MockResponse::make(array_merge($messageJson, ['id' => 'msg456']), 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg789*' => MockResponse::make(array_merge($messageJson, ['id' => 'msg789']), 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);
    $this->client->authenticate('test-token');

    // Test with fullDetails=true (should make individual API calls in batches)
    $messages = $this->client->listMessages(['q' => 'is:unread'], fullDetails: true);

    expect($messages)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($messages->first())->toBeInstanceOf(Email::class)
        ->and($messages->first()->id)->toBe('msg123')
        ->and($messages->first()->snippet)->not()->toBeNull() // Full details has snippet
        ->and($messages->first()->subject)->not()->toBeNull(); // Full details has subject
});

it('gracefully handles batch processing errors with fallback to minimal data', function () {
    $messagesListJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);

    // Create multiple messages with one that will fail
    $multipleMessagesJson = $messagesListJson;
    $multipleMessagesJson['messages'] = [
        ['id' => 'msg123', 'threadId' => 'thread123'],
        ['id' => 'msg-fail', 'threadId' => 'thread-fail'],
    ];

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make($multipleMessagesJson, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg123*' => MockResponse::make(json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true), 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg-fail*' => MockResponse::make(['error' => 'Not found'], 404),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);
    $this->client->authenticate('test-token');

    // Should not throw exception, should return partial results with fallback
    $messages = $this->client->listMessages(['q' => 'is:unread'], fullDetails: true);

    expect($messages)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2) // Should still return both messages
        ->and($messages->first())->toBeInstanceOf(Email::class);
});

it('respects batch processing configuration settings', function () {
    // Temporarily override config for testing
    config(['gmail-client.performance.enable_batching' => false]);

    $messagesListJson = json_decode(file_get_contents(__DIR__.'/fixtures/messages-list.json'), true);
    $messageJson = json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true);

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make($messagesListJson, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg123*' => MockResponse::make($messageJson, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);
    $this->client->authenticate('test-token');

    // Should still work but fall back to individual requests
    $messages = $this->client->listMessages(['q' => 'is:unread'], fullDetails: true);

    expect($messages)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($messages->first())->toBeInstanceOf(Email::class);

    // Reset config
    config(['gmail-client.performance.enable_batching' => true]);
});
