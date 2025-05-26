<?php

use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\GmailClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    $this->client = new GmailClient;
    $this->client->authenticate('fake-token');
    $this->messageId = 'message123';
    $this->labelIds = ['STARRED', 'IMPORTANT'];

    // Base mock response structure
    $this->baseMockResponse = [
        'id' => 'message123',
        'threadId' => 'thread123',
        'snippet' => 'Test message',
        'sizeEstimate' => 5000,
        'internalDate' => '1624982400000',
        'payload' => [
            'headers' => [
                ['name' => 'Subject', 'value' => 'Test Subject'],
                ['name' => 'From', 'value' => 'test@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
            ],
            'body' => ['data' => base64_encode('Test message body')],
        ],
    ];
});

test('can add labels to a message successfully', function () {
    $mockResponse = array_merge($this->baseMockResponse, [
        'labelIds' => ['INBOX', 'STARRED', 'IMPORTANT'],
    ]);

    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make($mockResponse, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->addLabelsToMessage($this->messageId, $this->labelIds);

    expect($result)->toBeInstanceOf(Email::class);
    expect($result->id)->toBe($this->messageId);
    expect($result->labelIds)->toContain('STARRED');
    expect($result->labelIds)->toContain('IMPORTANT');
});

test('can remove labels from a message successfully', function () {
    $mockResponse = array_merge($this->baseMockResponse, [
        'labelIds' => ['INBOX'], // STARRED and IMPORTANT removed
    ]);

    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make($mockResponse, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->removeLabelsFromMessage($this->messageId, $this->labelIds);

    expect($result)->toBeInstanceOf(Email::class);
    expect($result->id)->toBe($this->messageId);
    expect($result->labelIds)->not->toContain('STARRED');
    expect($result->labelIds)->not->toContain('IMPORTANT');
    expect($result->labelIds)->toContain('INBOX');
});

test('can modify labels by adding and removing in single request', function () {
    $mockResponse = array_merge($this->baseMockResponse, [
        'labelIds' => ['INBOX', 'STARRED'], // Added STARRED, removed UNREAD
    ]);

    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make($mockResponse, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->modifyMessageLabels(
        $this->messageId,
        ['STARRED'], // add
        ['UNREAD']   // remove
    );

    expect($result)->toBeInstanceOf(Email::class);
    expect($result->id)->toBe($this->messageId);
    expect($result->labelIds)->toContain('STARRED');
    expect($result->labelIds)->not->toContain('UNREAD');
});

test('can star a message using addLabelsToMessage', function () {
    $mockResponse = array_merge($this->baseMockResponse, [
        'labelIds' => ['INBOX', 'STARRED'],
    ]);

    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make($mockResponse, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->addLabelsToMessage($this->messageId, ['STARRED']);

    expect($result->labelIds)->toContain('STARRED');
});

test('can archive message by removing INBOX label', function () {
    $mockResponse = array_merge($this->baseMockResponse, [
        'labelIds' => [], // INBOX removed (archived)
    ]);

    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make($mockResponse, 200),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    $result = $this->client->removeLabelsFromMessage($this->messageId, ['INBOX']);

    expect($result->labelIds)->not->toContain('INBOX');
});

test('addLabelsToMessage throws NotFoundException when message not found', function () {
    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make([
            'error' => ['code' => 404, 'message' => 'Message not found'],
        ], 404),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    expect(fn () => $this->client->addLabelsToMessage($this->messageId, $this->labelIds))
        ->toThrow(NotFoundException::class);
});

test('addLabelsToMessage throws AuthenticationException when token invalid', function () {
    $mockClient = new MockClient([
        '*users/me/messages/*/modify' => MockResponse::make([
            'error' => ['code' => 401, 'message' => 'Invalid credentials'],
        ], 401),
    ]);

    $this->client->getConnector()->withMockClient($mockClient);

    expect(fn () => $this->client->addLabelsToMessage($this->messageId, $this->labelIds))
        ->toThrow(AuthenticationException::class);
});
