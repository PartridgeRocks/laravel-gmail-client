<?php

use PartridgeRocks\GmailClient\Data\Contact;
use PartridgeRocks\GmailClient\Data\Email;

describe('Email Contact Integration', function () {
    it('automatically parses contacts from API response', function () {
        $apiData = [
            'id' => 'msg-123',
            'threadId' => 'thread-123',
            'labelIds' => ['INBOX'],
            'snippet' => 'Hello world',
            'sizeEstimate' => 1000,
            'internalDate' => '1577836800000', // 2020-01-01
            'payload' => [
                'headers' => [
                    ['name' => 'From', 'value' => '"John Doe" <john@company.com>'],
                    ['name' => 'To', 'value' => 'jane@example.com, "Bob Smith" <bob@example.com>'],
                    ['name' => 'Cc', 'value' => 'admin@example.com'],
                    ['name' => 'Subject', 'value' => 'Test email'],
                ],
                'body' => [
                    'data' => base64_encode('Hello world'),
                ],
            ],
        ];

        $email = Email::fromApiResponse($apiData);

        // Check that contacts are parsed
        expect($email->fromContact)->toBeInstanceOf(Contact::class);
        expect($email->fromContact->name)->toBe('John Doe');
        expect($email->fromContact->email)->toBe('john@company.com');
        expect($email->fromContact->domain)->toBe('company.com');

        expect($email->toContacts)->toHaveCount(2);
        expect($email->toContacts[0]->email)->toBe('jane@example.com');
        expect($email->toContacts[0]->name)->toBeNull();
        expect($email->toContacts[1]->name)->toBe('Bob Smith');
        expect($email->toContacts[1]->email)->toBe('bob@example.com');

        expect($email->ccContacts)->toHaveCount(1);
        expect($email->ccContacts[0]->email)->toBe('admin@example.com');

        // Check backward compatibility - original string properties still work
        expect($email->from)->toBe('"John Doe" <john@company.com>');
        expect($email->to)->toBe(['jane@example.com', '"Bob Smith" <bob@example.com>']);
        expect($email->cc)->toBe(['admin@example.com']);
    });

    it('handles emails without contact fields', function () {
        $apiData = [
            'id' => 'msg-123',
            'threadId' => 'thread-123',
            'labelIds' => ['INBOX'],
            'snippet' => 'Hello world',
            'sizeEstimate' => 1000,
            'internalDate' => '1577836800000',
            'payload' => [
                'headers' => [
                    ['name' => 'Subject', 'value' => 'No contacts'],
                ],
            ],
        ];

        $email = Email::fromApiResponse($apiData);

        expect($email->fromContact)->toBeNull();
        expect($email->toContacts)->toBeNull();
        expect($email->ccContacts)->toBeNull();
        expect($email->bccContacts)->toBeNull();
    });

    describe('contact utility methods', function () {
        beforeEach(function () {
            // Create a sample email with various contacts
            $this->email = new Email(
                id: 'msg-123',
                threadId: 'thread-123',
                labelIds: ['INBOX'],
                snippet: 'Test email',
                payload: [],
                sizeEstimate: 1000,
                internalDate: now(),
                fromContact: new Contact('sender@company.com', 'Sender Name'),
                toContacts: [
                    new Contact('recipient1@example.com', 'Recipient One'),
                    new Contact('recipient2@company.com'),
                ],
                ccContacts: [
                    new Contact('cc@other.com', 'CC Person'),
                ],
                bccContacts: [
                    new Contact('bcc@company.com'),
                ]
            );
        });

        it('gets all recipients', function () {
            $recipients = $this->email->getAllRecipients();

            expect($recipients)->toHaveCount(4);
            expect($recipients[0]->email)->toBe('recipient1@example.com');
            expect($recipients[1]->email)->toBe('recipient2@company.com');
            expect($recipients[2]->email)->toBe('cc@other.com');
            expect($recipients[3]->email)->toBe('bcc@company.com');
        });

        it('gets all contacts including sender', function () {
            $contacts = $this->email->getAllContacts();

            expect($contacts)->toHaveCount(5);
            expect($contacts[0]->email)->toBe('sender@company.com'); // Sender is first
            expect($contacts[1]->email)->toBe('recipient1@example.com');
            expect($contacts[4]->email)->toBe('bcc@company.com');
        });

        it('gets unique contact domains', function () {
            $domains = $this->email->getContactDomains();

            expect($domains)->toHaveCount(3);
            expect($domains)->toContain('company.com');
            expect($domains)->toContain('example.com');
            expect($domains)->toContain('other.com');
        });

        it('checks if email has contact from specific domain', function () {
            expect($this->email->hasContactFromDomain('company.com'))->toBeTrue();
            expect($this->email->hasContactFromDomain('example.com'))->toBeTrue();
            expect($this->email->hasContactFromDomain('nonexistent.com'))->toBeFalse();
        });

        it('gets contacts from specific domain', function () {
            $companyContacts = $this->email->getContactsFromDomain('company.com');

            expect($companyContacts)->toHaveCount(3);

            $emails = array_map(fn ($contact) => $contact->email, $companyContacts);
            expect($emails)->toContain('sender@company.com');
            expect($emails)->toContain('recipient2@company.com');
            expect($emails)->toContain('bcc@company.com');
        });

        it('handles email with no contacts gracefully', function () {
            $emptyEmail = new Email(
                id: 'msg-123',
                threadId: 'thread-123',
                labelIds: ['INBOX'],
                snippet: 'Test email',
                payload: [],
                sizeEstimate: 1000,
                internalDate: now()
            );

            expect($emptyEmail->getAllContacts())->toHaveCount(0);
            expect($emptyEmail->getAllRecipients())->toHaveCount(0);
            expect($emptyEmail->getContactDomains())->toHaveCount(0);
            expect($emptyEmail->hasContactFromDomain('example.com'))->toBeFalse();
            expect($emptyEmail->getContactsFromDomain('example.com'))->toHaveCount(0);
        });
    });

    describe('CRM integration examples', function () {
        it('enables easy contact matching for CRM', function () {
            $email = new Email(
                id: 'msg-123',
                threadId: 'thread-123',
                labelIds: ['INBOX'],
                snippet: 'Business proposal',
                payload: [],
                sizeEstimate: 1000,
                internalDate: now(),
                fromContact: new Contact('john.smith@acmecorp.com', 'John Smith'),
                toContacts: [
                    new Contact('sales@mycompany.com', 'Sales Team'),
                ]
            );

            // Example: Find contacts from external companies
            $externalContacts = array_filter(
                $email->getAllContacts(),
                fn ($contact) => ! $contact->isFromDomain('mycompany.com')
            );

            expect($externalContacts)->toHaveCount(1);
            expect($externalContacts[0]->email)->toBe('john.smith@acmecorp.com');
            expect($externalContacts[0]->name)->toBe('John Smith');
            expect($externalContacts[0]->domain)->toBe('acmecorp.com');

            // Example: Extract company information
            $senderCompany = $email->fromContact->domain;
            expect($senderCompany)->toBe('acmecorp.com');
        });

        it('supports domain-based company identification', function () {
            $email = new Email(
                id: 'msg-123',
                threadId: 'thread-123',
                labelIds: ['INBOX'],
                snippet: 'Meeting request',
                payload: [],
                sizeEstimate: 1000,
                internalDate: now(),
                fromContact: new Contact('jane@bigclient.com', 'Jane Doe'),
                toContacts: [
                    new Contact('team@mycompany.com'),
                ]
            );

            // Check if email involves important client domain
            $isFromImportantClient = $email->hasContactFromDomain('bigclient.com');
            expect($isFromImportantClient)->toBeTrue();

            // Get all contacts from that domain
            $clientContacts = $email->getContactsFromDomain('bigclient.com');
            expect($clientContacts)->toHaveCount(1);
            expect($clientContacts[0]->name)->toBe('Jane Doe');
        });
    });
});
