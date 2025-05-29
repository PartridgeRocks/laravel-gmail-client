<?php

namespace PartridgeRocks\GmailClient\Tests\Gmail\Pagination;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Tests\TestHelpers\MockClientAdapter;
use Saloon\Http\Faking\MockResponse;

it('can fetch the first page of results', function () {
    $connector = new GmailConnector;

    // First page of results with a next page token
    $mockClient = MockClientAdapter::create([
        '*messages*' => MockResponse::make([
            'messages' => [
                ['id' => 'msg1', 'threadId' => 'thread1'],
                ['id' => 'msg2', 'threadId' => 'thread2'],
            ],
            'nextPageToken' => 'page2token',
        ], 200),
    ]);

    $connector->withMockClient($mockClient);

    $paginator = new GmailPaginator(
        $connector,
        ListMessagesRequest::class,
        'messages',
        2
    );

    $results = $paginator->getNextPage();

    expect($results)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($paginator->hasMorePages())->toBeTrue()
        ->and($paginator->getPageToken())->toBe('page2token');
});

it('can fetch limited pages with memory controls', function () {
    $connector = new GmailConnector;

    // Simple static response - test the memory limit functionality
    $mockClient = MockClientAdapter::create([
        '*messages*' => MockResponse::make([
            'messages' => [
                ['id' => 'msg1', 'threadId' => 'thread1'],
                ['id' => 'msg2', 'threadId' => 'thread2'],
                ['id' => 'msg3', 'threadId' => 'thread3'],
                ['id' => 'msg4', 'threadId' => 'thread4'],
                ['id' => 'msg5', 'threadId' => 'thread5'],
            ],
            // No nextPageToken means this is all the data
        ], 200),
    ]);

    $connector->withMockClient($mockClient);

    $paginator = new GmailPaginator(
        $connector,
        ListMessagesRequest::class,
        'messages',
        10 // Large page size
    );

    // Test with memory limit - should stop at 3 items even though more are available
    $results = $paginator->getAllPages(3);

    expect($results)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($results->pluck('id')->toArray())->toBe(['msg1', 'msg2', 'msg3']);
});

it('can transform results using a DTO', function () {
    $connector = new GmailConnector;

    // Simple one-page response
    $mockClient = MockClientAdapter::create([
        '*messages*' => MockResponse::make([
            'messages' => [
                [
                    'id' => 'msg1',
                    'threadId' => 'thread1',
                    'payload' => [
                        'headers' => [
                            ['name' => 'Subject', 'value' => 'Test Subject'],
                            ['name' => 'From', 'value' => 'test@example.com'],
                        ],
                    ],
                    'sizeEstimate' => 1000,
                    'internalDate' => '1624982400000',
                ],
            ],
        ], 200),
    ]);

    $connector->withMockClient($mockClient);

    $paginator = new GmailPaginator(
        $connector,
        ListMessagesRequest::class,
        'messages',
        2
    );

    $results = $paginator->transformUsingDTO(EmailDTO::class);

    expect($results)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($results->first())->toBeInstanceOf(EmailDTO::class)
        ->and($results->first()->id)->toBe('msg1')
        ->and($results->first()->subject)->toBe('Test Subject');
});
