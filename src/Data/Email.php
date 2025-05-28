<?php

namespace PartridgeRocks\GmailClient\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

/**
 * Gmail Email Data Object - represents a complete Gmail message.
 *
 * This data class encapsulates all properties and metadata of a Gmail message,
 * providing structured access to headers, body content, recipients, and labels.
 * Includes automatic parsing of contact information and utility methods for
 * common email operations like domain filtering and recipient management.
 *
 * Key Features:
 * - Automatic header parsing and normalization
 * - Base64 body content decoding
 * - Contact parsing with domain extraction
 * - Label and thread management
 * - CRM-friendly contact utilities
 *
 * @see https://developers.google.com/gmail/api/reference/rest/v1/users.messages#Message
 */
class Email extends Data
{
    /**
     * Initializes a new Email data object with the specified attributes.
     *
     * @param  string  $id  Unique identifier of the email.
     * @param  string  $threadId  Identifier of the email thread.
     * @param  array<string>  $labelIds  Labels associated with the email.
     * @param  string|null  $snippet  Short snippet of the email content.
     * @param  array<string, mixed>  $payload  Raw payload data of the email.
     * @param  int  $sizeEstimate  Estimated size of the email in bytes.
     * @param  Carbon  $internalDate  Date and time the email was received.
     * @param  array<string, string>|null  $headers  Optional array of email headers.
     * @param  string|null  $body  Optional decoded body content of the email.
     * @param  string|null  $subject  Optional subject line of the email.
     * @param  string|null  $from  Optional sender information.
     * @param  array<string>|null  $to  Optional list of recipient addresses.
     * @param  array<string>|null  $cc  Optional list of CC recipient addresses.
     * @param  array<string>|null  $bcc  Optional list of BCC recipient addresses.
     * @param  Contact|null  $fromContact  Optional parsed sender contact.
     * @param  array<Contact>|null  $toContacts  Optional list of parsed recipient contacts.
     * @param  array<Contact>|null  $ccContacts  Optional list of parsed CC contacts.
     * @param  array<Contact>|null  $bccContacts  Optional list of parsed BCC contacts.
     */
    public function __construct(
        public string $id,
        public string $threadId,
        /** @var array<string> */
        public array $labelIds,
        public ?string $snippet,
        /** @var array<string, mixed> */
        public array $payload,
        public int $sizeEstimate,
        #[WithCast(DateTimeInterfaceCast::class)]
        public Carbon $internalDate,
        /** @var array<string, string>|null */
        public ?array $headers = null,
        public ?string $body = null,
        public ?string $subject = null,
        public ?string $from = null,
        /** @var array<string>|null */
        public ?array $to = null,
        /** @var array<string>|null */
        public ?array $cc = null,
        /** @var array<string>|null */
        public ?array $bcc = null,
        public ?Contact $fromContact = null,
        /** @var array<Contact>|null */
        public ?array $toContacts = null,
        /** @var array<Contact>|null */
        public ?array $ccContacts = null,
        /** @var array<Contact>|null */
        public ?array $bccContacts = null
    ) {}

    /**
     * Creates an Email instance from a raw Gmail API response array.
     *
     * Parses and normalizes header fields, decodes the email body, and extracts relevant metadata to populate the Email object.
     *
     * @param  array<string, mixed>  $data  Raw Gmail API response data for a single message.
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
                        $to = array_map('trim', explode(',', $header['value']));
                        break;
                    case 'Cc':
                        $cc = array_map('trim', explode(',', $header['value']));
                        break;
                    case 'Bcc':
                        $bcc = array_map('trim', explode(',', $header['value']));
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

        // Parse contacts from the string data
        $fromContact = $from ? Contact::parse($from) : null;
        $toContacts = $to ? Contact::parseMultiple(implode(', ', $to)) : null;
        $ccContacts = $cc ? Contact::parseMultiple(implode(', ', $cc)) : null;
        $bccContacts = $bcc ? Contact::parseMultiple(implode(', ', $bcc)) : null;

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
            bcc: $bcc,
            fromContact: $fromContact,
            toContacts: $toContacts,
            ccContacts: $ccContacts,
            bccContacts: $bccContacts
        );
    }

    /**
     * Get all recipients (to, cc, bcc) as Contact objects.
     *
     * @return array<Contact>
     */
    public function getAllRecipients(): array
    {
        $recipients = [];

        if ($this->toContacts) {
            $recipients = array_merge($recipients, $this->toContacts);
        }

        if ($this->ccContacts) {
            $recipients = array_merge($recipients, $this->ccContacts);
        }

        if ($this->bccContacts) {
            $recipients = array_merge($recipients, $this->bccContacts);
        }

        return $recipients;
    }

    /**
     * Get all contacts involved in this email (sender + all recipients).
     *
     * @return array<Contact>
     */
    public function getAllContacts(): array
    {
        $contacts = $this->getAllRecipients();

        if ($this->fromContact) {
            array_unshift($contacts, $this->fromContact);
        }

        return $contacts;
    }

    /**
     * Get unique email domains from all contacts.
     *
     * @return array<string>
     */
    public function getContactDomains(): array
    {
        $domains = [];

        foreach ($this->getAllContacts() as $contact) {
            if ($contact->domain) {
                $domains[] = $contact->domain;
            }
        }

        return array_unique($domains);
    }

    /**
     * Check if any contact belongs to a specific domain.
     *
     * @param  string  $domain  The domain to check
     */
    public function hasContactFromDomain(string $domain): bool
    {
        foreach ($this->getAllContacts() as $contact) {
            if ($contact->isFromDomain($domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get contacts that belong to a specific domain.
     *
     * @param  string  $domain  The domain to filter by
     * @return array<Contact>
     */
    public function getContactsFromDomain(string $domain): array
    {
        return array_filter(
            $this->getAllContacts(),
            fn (Contact $contact) => $contact->isFromDomain($domain)
        );
    }
}
