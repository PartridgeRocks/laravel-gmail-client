<?php

namespace PartridgeRocks\GmailClient\Tests\Gmail\Pagination;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use Saloon\Http\Faking\MockResponse;
use PartridgeRocks\GmailClient\Tests\TestHelpers\MockClientAdapter;

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

it('can fetch all pages of results', function () {
    $connector = new GmailConnector;

    // Respond with different data based on the page token
    $mockClient = MockClientAdapter::create([
        '*users/me/messages' => function($request) {
            // First page (no token)
            if (!isset($request->query['pageToken'])) {
                return MockResponse::make([
                    'messages' => [
                        ['id' => 'msg1', 'threadId' => 'thread1'],
                        ['id' => 'msg2', 'threadId' => 'thread2'],
                    ],
                    'nextPageToken' => 'page2token',
                ], 200);
            }
            // Second page
            else if ($request->query['pageToken'] === 'page2token') {
                return MockResponse::make([
                    'messages' => [
                        ['id' => 'msg3', 'threadId' => 'thread3'],
                        ['id' => 'msg4', 'threadId' => 'thread4'],
                    ],
                    'nextPageToken' => 'page3token',
                ], 200);
            }
            // Third page (last)
            else if ($request->query['pageToken'] === 'page3token') {
                return MockResponse::make([
                    'messages' => [
                        ['id' => 'msg5', 'threadId' => 'thread5'],
                    ],
                    // No next page token means this is the last page
                ], 200);
            }
        }
    ]);

    $connector->withMockClient($mockClient);

    $paginator = new GmailPaginator(
        $connector,
        ListMessagesRequest::class,
        'messages',
        2
    );

    $results = $paginator->getAllPages();

    expect($results)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(5)
        ->and($paginator->hasMorePages())->toBeFalse()
        ->and($paginator->getPageToken())->toBeNull();
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
