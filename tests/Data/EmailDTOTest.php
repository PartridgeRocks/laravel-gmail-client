<?php

namespace PartridgeRocks\GmailClient\Tests\Data;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;

it('can create from API response', function () {
    $data = json_decode(file_get_contents(__DIR__.'/../fixtures/message.json'), true);

    $email = EmailDTO::fromApiResponse($data);

    expect($email)
        ->toBeInstanceOf(EmailDTO::class)
        ->and($email->id)->toBe('msg123')
        ->and($email->threadId)->toBe('thread123')
        ->and($email->subject)->toBe('Test Email Subject')
        ->and($email->from)->toBe('test@example.com')
        ->and($email->to)->toBeArray()
        ->and($email->to)->toContain('recipient@example.com')
        ->and($email->internalDate)->toBeInstanceOf(Carbon::class)
        ->and($email->body)->toBe('This is a test email body');
});

it('extracts headers correctly', function () {
    $data = json_decode(file_get_contents(__DIR__.'/../fixtures/message.json'), true);

    $email = EmailDTO::fromApiResponse($data);

    expect($email->headers)
        ->toBeArray()
        ->toHaveKey('From', 'test@example.com')
        ->toHaveKey('Subject', 'Test Email Subject')
        ->toHaveKey('To', 'recipient@example.com');
});

it('decodes base64 body content', function () {
    $data = json_decode(file_get_contents(__DIR__.'/../fixtures/message.json'), true);

    $email = EmailDTO::fromApiResponse($data);

    // The fixture contains "VGhpcyBpcyBhIHRlc3QgZW1haWwgYm9keQ==" which decodes to "This is a test email body"
    expect($email->body)->toBe('This is a test email body');
});

it('handles missing fields gracefully', function () {
    $data = [
        'id' => 'msg123',
        'threadId' => 'thread123',
        'sizeEstimate' => 5000,
        'internalDate' => '1624982400000',
    ];

    $email = EmailDTO::fromApiResponse($data);

    expect($email)
        ->toBeInstanceOf(EmailDTO::class)
        ->and($email->id)->toBe('msg123')
        ->and($email->threadId)->toBe('thread123')
        ->and($email->subject)->toBeNull()
        ->and($email->from)->toBeNull()
        ->and($email->to)->toBeNull()
        ->and($email->body)->toBeNull();
});

it('can create a collection from API response', function () {
    $data = json_decode(file_get_contents(__DIR__.'/../fixtures/messages-list.json'), true);
    $messageData = json_decode(file_get_contents(__DIR__.'/../fixtures/message.json'), true);

    // In a real scenario, we'd have the full message data in the list response
    $data['messages'][0] = $messageData;

    $emails = EmailDTO::collectionFromApiResponse($data);

    expect($emails)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($emails->first())->toBeInstanceOf(EmailDTO::class)
        ->and($emails->first()->id)->toBe('msg123');
});
