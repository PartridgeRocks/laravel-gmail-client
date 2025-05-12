<?php

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\GmailClient;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function () {
    $this->client = new GmailClient();
});

it('can be instantiated', function () {
    expect($this->client)->toBeInstanceOf(GmailClient::class);
});

it('requires authentication', function () {
    // Without calling authenticate(), operations should fail
    $this->client->getMessage('test-id');
})->throws(AuthenticationException::class, 'No access token was provided for authentication');

it('can authenticate with a token', function () {
    $token = 'test-access-token';
    
    $client = $this->client->authenticate($token);
    
    expect($client)->toBeInstanceOf(GmailClient::class);
});

it('can list messages', function () {
    $messagesJson = file_get_contents(__DIR__ . '/fixtures/messages-list.json');
    $messageJson = file_get_contents(__DIR__ . '/fixtures/message.json');
    
    // Mock API responses
    Saloon::fake([
        '*messages*' => MockResponse::make($messagesJson, 200),
        '*messages/msg123*' => MockResponse::make($messageJson, 200),
    ]);
    
    $this->client->authenticate('test-token');
    $messages = $this->client->listMessages();
    
    expect($messages)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($messages->first())->toBeInstanceOf(Email::class);
});

it('can get paginated messages', function () {
    $this->client->authenticate('test-token');
    $paginator = $this->client->listMessages([], true);
    
    expect($paginator)->toBeInstanceOf(GmailPaginator::class);
});

it('can get a single message', function () {
    $messageJson = file_get_contents(__DIR__ . '/fixtures/message.json');
    
    Saloon::fake([
        '*messages/msg123*' => MockResponse::make($messageJson, 200),
    ]);
    
    $this->client->authenticate('test-token');
    $message = $this->client->getMessage('msg123');
    
    expect($message)
        ->toBeInstanceOf(Email::class)
        ->and($message->id)->toBe('msg123')
        ->and($message->subject)->not()->toBeNull();
});

it('validates email addresses when sending', function () {
    $this->client->authenticate('test-token');
    
    $this->client->sendEmail('invalid-email', 'Test Subject', 'Test body');
})->throws(ValidationException::class, 'The email address "invalid-email" is not valid');

it('can list labels', function () {
    $labelsJson = file_get_contents(__DIR__ . '/fixtures/labels-list.json');
    
    Saloon::fake([
        '*labels*' => MockResponse::make($labelsJson, 200),
    ]);
    
    $this->client->authenticate('test-token');
    $labels = $this->client->listLabels();
    
    expect($labels)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($labels->first())->toBeInstanceOf(Label::class);
});

it('can create a label', function () {
    $labelJson = file_get_contents(__DIR__ . '/fixtures/label.json');
    
    Saloon::fake([
        '*labels*' => MockResponse::make($labelJson, 200),
    ]);
    
    $this->client->authenticate('test-token');
    $label = $this->client->createLabel('Test Label');
    
    expect($label)
        ->toBeInstanceOf(Label::class)
        ->and($label->name)->toBe('Test Label');
});