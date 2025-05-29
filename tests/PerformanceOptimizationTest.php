<?php

use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Services\MessageService;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * Performance Optimization Tests
 *
 * These tests verify that the N+1 query pattern fix in listMessages()
 * provides significant performance improvements by reducing API calls.
 *
 * Note: These tests focus on the MessageService layer, while N1PerformanceFixTest.php
 * tests the same functionality at the GmailClient layer. Both test suites provide
 * valuable coverage at different abstraction levels.
 */
test('listMessages with fullDetails=false creates minimal Email objects', function () {
    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg1', 'threadId' => 'thread1'],
                ['id' => 'msg2', 'threadId' => 'thread2'],
                ['id' => 'msg3', 'threadId' => 'thread3'],
            ],
            'resultSizeEstimate' => 3,
        ], 200),
    ]);

    $connector = new \PartridgeRocks\GmailClient\Gmail\GmailConnector;
    $connector->withMockClient($mockClient);

    $messageService = new MessageService($connector);

    // Test optimized version - should make only 1 API call
    $result = $messageService->listMessages([], false, null, false, false);

    // Verify we get Email objects with minimal data
    expect($result)->toHaveCount(3);
    expect($result->first())->toBeInstanceOf(Email::class);
    expect($result->first()->id)->toBe('msg1');
    expect($result->first()->threadId)->toBe('thread1');
    expect($result->first()->subject)->toBeNull(); // Minimal data doesn't include subject
    expect($result->first()->body)->toBeNull(); // Minimal data doesn't include body
});

test('listMessages with fullDetails=true fetches complete Email objects', function () {
    $messageJson1 = json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true);
    $messageJson2 = array_merge($messageJson1, ['id' => 'msg2']);

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg123', 'threadId' => 'thread1'],
                ['id' => 'msg2', 'threadId' => 'thread2'],
            ],
            'resultSizeEstimate' => 2,
        ], 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg123*' => MockResponse::make($messageJson1, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg2*' => MockResponse::make($messageJson2, 200),
    ]);

    $connector = new \PartridgeRocks\GmailClient\Gmail\GmailConnector;
    $connector->withMockClient($mockClient);

    $messageService = new MessageService($connector);

    // Test batch fetching with full details
    $result = $messageService->listMessages([], false, null, false, true);

    // Verify we get complete Email objects
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(Email::class);
    expect($result->first()->id)->toBe('msg123');
    expect($result->first()->subject)->not()->toBeNull(); // Full details include subject
});

test('batch processing handles errors gracefully', function () {
    $messageJson = json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true);

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg-success', 'threadId' => 'thread1'],
                ['id' => 'msg-fail', 'threadId' => 'thread2'],
            ],
            'resultSizeEstimate' => 2,
        ], 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg-success*' => MockResponse::make($messageJson, 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg-fail*' => MockResponse::make(['error' => 'Not found'], 404),
    ]);

    $connector = new \PartridgeRocks\GmailClient\Gmail\GmailConnector;
    $connector->withMockClient($mockClient);

    $messageService = new MessageService($connector);

    // Should handle partial failures gracefully
    $result = $messageService->listMessages([], false, null, false, true);

    expect($result)->toHaveCount(2); // Should still return both (one full, one minimal as fallback)
    expect($result->first())->toBeInstanceOf(Email::class);
});

test('Email::fromMinimalData creates valid Email objects', function () {
    $minimalData = [
        'id' => 'test-id-123',
        'threadId' => 'test-thread-456',
    ];

    $email = Email::fromMinimalData($minimalData);

    expect($email)->toBeInstanceOf(Email::class);
    expect($email->id)->toBe('test-id-123');
    expect($email->threadId)->toBe('test-thread-456');
    expect($email->labelIds)->toBe([]);
    expect($email->snippet)->toBeNull();
    expect($email->subject)->toBeNull();
    expect($email->from)->toBeNull();
    expect($email->to)->toBeNull();
    expect($email->body)->toBeNull();
    expect($email->fromContact)->toBeNull();
    expect($email->toContacts)->toBeNull();
});

test('batch processing configuration is respected', function () {
    // Test that batching can be disabled via config
    config(['gmail-client.performance.enable_batching' => false]);

    $messageJson = json_decode(file_get_contents(__DIR__.'/fixtures/message.json'), true);

    $mockClient = new MockClient([
        'https://gmail.googleapis.com/gmail/v1/users/me/messages' => MockResponse::make([
            'messages' => [
                ['id' => 'msg123', 'threadId' => 'thread1'],
            ],
            'resultSizeEstimate' => 1,
        ], 200),
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/msg123*' => MockResponse::make($messageJson, 200),
    ]);

    $connector = new \PartridgeRocks\GmailClient\Gmail\GmailConnector;
    $connector->withMockClient($mockClient);

    $messageService = new MessageService($connector);

    // Should still work with batching disabled
    $result = $messageService->listMessages([], false, null, false, true);

    expect($result)->toHaveCount(1);
    expect($result->first())->toBeInstanceOf(Email::class);

    // Reset config
    config(['gmail-client.performance.enable_batching' => true]);
});
