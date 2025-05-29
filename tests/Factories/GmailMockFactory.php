<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Tests\Factories;

use PartridgeRocks\GmailClient\Tests\Builders\EmailBuilder;
use PartridgeRocks\GmailClient\Tests\Builders\LabelBuilder;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

class GmailMockFactory
{
    public static function createMessageListMock(array $messages = [], ?string $nextPageToken = null): MockClient
    {
        $responseData = [];
        foreach ($messages as $message) {
            if ($message instanceof EmailBuilder) {
                $responseData[] = $message->buildApiResponse();
            } elseif (is_array($message)) {
                $responseData[] = $message;
            } else {
                $responseData[] = EmailBuilder::create()->buildApiResponse();
            }
        }

        $response = [
            'messages' => $responseData,
            'resultSizeEstimate' => count($responseData),
        ];

        if ($nextPageToken) {
            $response['nextPageToken'] = $nextPageToken;
        }

        return new MockClient([
            'gmail.googleapis.com/gmail/v1/users/me/messages*' => MockResponse::make($response, 200),
        ]);
    }

    public static function createMessageGetMock(EmailBuilder $emailBuilder): MockClient
    {
        return new MockClient([
            'gmail.googleapis.com/gmail/v1/users/me/messages/*' => MockResponse::make($emailBuilder->buildApiResponse(), 200),
        ]);
    }

    public static function createLabelListMock(array $labels = []): MockClient
    {
        $responseData = [];
        foreach ($labels as $label) {
            if ($label instanceof LabelBuilder) {
                $responseData[] = $label->buildApiResponse();
            } elseif (is_array($label)) {
                $responseData[] = $label;
            } else {
                $responseData[] = LabelBuilder::create()->buildApiResponse();
            }
        }

        $response = [
            'labels' => $responseData,
        ];

        return new MockClient([
            'gmail.googleapis.com/gmail/v1/users/me/labels*' => MockResponse::make($response, 200),
        ]);
    }

    public static function createLabelGetMock(LabelBuilder $labelBuilder): MockClient
    {
        return new MockClient([
            'gmail.googleapis.com/gmail/v1/users/me/labels/*' => MockResponse::make($labelBuilder->buildApiResponse(), 200),
        ]);
    }

    public static function createAuthSuccessMock(string $accessToken = 'test-access-token'): MockClient
    {
        return new MockClient([
            'oauth2.googleapis.com/token' => MockResponse::make([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'test-refresh-token',
                'scope' => 'https://www.googleapis.com/auth/gmail.readonly',
            ], 200),
        ]);
    }

    public static function createAuthErrorMock(string $error = 'invalid_grant'): MockClient
    {
        return new MockClient([
            'oauth2.googleapis.com/token' => MockResponse::make([
                'error' => $error,
                'error_description' => 'Invalid authorization code.',
            ], 400),
        ]);
    }

    public static function createRateLimitMock(): MockClient
    {
        return new MockClient([
            '*' => MockResponse::make([
                'error' => [
                    'code' => 429,
                    'message' => 'Rate limit exceeded',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429, ['Retry-After' => '60']),
        ]);
    }

    public static function createNotFoundMock(): MockClient
    {
        return new MockClient([
            '*' => MockResponse::make([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                ],
            ], 404),
        ]);
    }

    public static function createUnauthorizedMock(): MockClient
    {
        return new MockClient([
            '*' => MockResponse::make([
                'error' => [
                    'code' => 401,
                    'message' => 'Request had invalid authentication credentials.',
                    'status' => 'UNAUTHENTICATED',
                ],
            ], 401),
        ]);
    }

    public static function createServerErrorMock(): MockClient
    {
        return new MockClient([
            '*' => MockResponse::make([
                'error' => [
                    'code' => 500,
                    'message' => 'Internal server error.',
                    'status' => 'INTERNAL',
                ],
            ], 500),
        ]);
    }

    public static function createSendEmailSuccessMock(string $messageId = 'sent-message-id'): MockClient
    {
        return new MockClient([
            'gmail.googleapis.com/gmail/v1/users/me/messages/send' => MockResponse::make([
                'id' => $messageId,
                'threadId' => 'thread-'.$messageId,
                'labelIds' => ['SENT'],
            ], 200),
        ]);
    }

    public static function createModifyMessageMock(string $messageId, array $addedLabels = [], array $removedLabels = []): MockClient
    {
        return new MockClient([
            "gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}/modify" => MockResponse::make([
                'id' => $messageId,
                'threadId' => 'thread-'.$messageId,
                'labelIds' => array_merge(['INBOX'], $addedLabels),
            ], 200),
        ]);
    }

    public static function createDefaultSystemLabels(): array
    {
        return [
            LabelBuilder::create()->inbox(),
            LabelBuilder::create()->sent(),
            LabelBuilder::create()->drafts(),
            LabelBuilder::create()->starred(),
        ];
    }

    public static function createSampleEmails(int $count = 5): array
    {
        $emails = [];

        for ($i = 1; $i <= $count; $i++) {
            $builder = EmailBuilder::create()
                ->withId("email-{$i}")
                ->withSubject("Test Email {$i}")
                ->withFrom("sender{$i}@example.com")
                ->withTo("recipient{$i}@example.com")
                ->withBody("This is test email number {$i}");

            if ($i % 3 === 0) {
                $builder->unread();
            }
            if ($i % 4 === 0) {
                $builder->starred();
            }

            $emails[] = $builder;
        }

        return $emails;
    }
}
