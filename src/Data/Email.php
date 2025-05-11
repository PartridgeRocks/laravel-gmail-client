<?php

namespace PartridgeRocks\GmailClient\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class Email extends Data
{
    /**
     * Initializes a new Email data object with the specified attributes.
     *
     * @param  string  $id  Unique identifier of the email.
     * @param  string  $threadId  Identifier of the email thread.
     * @param  array  $labelIds  Labels associated with the email.
     * @param  string|null  $snippet  Short snippet of the email content.
     * @param  array  $payload  Raw payload data of the email.
     * @param  int  $sizeEstimate  Estimated size of the email in bytes.
     * @param  Carbon  $internalDate  Date and time the email was received.
     * @param  array|null  $headers  Optional array of email headers.
     * @param  string|null  $body  Optional decoded body content of the email.
     * @param  string|null  $subject  Optional subject line of the email.
     * @param  string|null  $from  Optional sender information.
     * @param  array|null  $to  Optional list of recipient addresses.
     * @param  array|null  $cc  Optional list of CC recipient addresses.
     * @param  array|null  $bcc  Optional list of BCC recipient addresses.
     */
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
    ) {}

    /**
     * Creates an Email instance from a raw Gmail API response array.
     *
     * Parses and normalizes header fields, decodes the email body, and extracts relevant metadata to populate the Email object.
     *
     * @param  array  $data  Raw Gmail API response data for a single message.
     * @return self Populated Email instance with structured fields and decoded content.
     */
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
