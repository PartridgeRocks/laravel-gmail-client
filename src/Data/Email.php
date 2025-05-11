<?php

namespace PartridgeRocks\GmailClient\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Attributes\WithCast;
use Carbon\Carbon;

class Email extends Data
{
    public function __construct(
        public string $id,
        public string $threadId,
        public array $labelIds,
        public ?string $snippet,
        public array $payload,
        public int $sizeEstimate,
        #[WithCast(DateTimeInterfaceCast::class)]
        public Carbon $internalDate,
        public ?array $headers = null,
        public ?string $body = null,
        public ?string $subject = null,
        public ?string $from = null,
        public ?array $to = null,
        public ?array $cc = null,
        public ?array $bcc = null
    ) {
    }

    public static function fromApiResponse(array $data): self
    {
        // Extract header info for easier access
        $headers = [];
        $subject = null;
        $from = null;
        $to = null;
        $cc = null;
        $bcc = null;
        $body = null;

        if (isset($data['payload']['headers'])) {
            foreach ($data['payload']['headers'] as $header) {
                $headers[$header['name']] = $header['value'];
                
                switch ($header['name']) {
                    case 'Subject':
                        $subject = $header['value'];
                        break;
                    case 'From':
                        $from = $header['value'];
                        break;
                    case 'To':
                        $to = explode(',', $header['value']);
                        break;
                    case 'Cc':
                        $cc = explode(',', $header['value']);
                        break;
                    case 'Bcc':
                        $bcc = explode(',', $header['value']);
                        break;
                }
            }
        }

        // Extract body
        if (isset($data['payload']['body']['data'])) {
            $body = base64_decode(strtr($data['payload']['body']['data'], '-_', '+/'));
        } elseif (isset($data['payload']['parts'])) {
            foreach ($data['payload']['parts'] as $part) {
                if ($part['mimeType'] === 'text/plain' && isset($part['body']['data'])) {
                    $body = base64_decode(strtr($part['body']['data'], '-_', '+/'));
                    break;
                }
            }
        }

        return new self(
            id: $data['id'],
            threadId: $data['threadId'],
            labelIds: $data['labelIds'] ?? [],
            snippet: $data['snippet'] ?? null,
            payload: $data['payload'] ?? [],
            sizeEstimate: $data['sizeEstimate'],
            internalDate: Carbon::createFromTimestampMs($data['internalDate']),
            headers: $headers,
            body: $body,
            subject: $subject,
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc
        );
    }
}