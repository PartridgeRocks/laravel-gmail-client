<?php

use PartridgeRocks\GmailClient\Data\Contact;

describe('Contact', function () {
    describe('parse method', function () {
        it('parses email with name in quotes', function () {
            $contact = Contact::parse('"John Doe" <john@example.com>');

            expect($contact->name)->toBe('John Doe');
            expect($contact->email)->toBe('john@example.com');
            expect($contact->domain)->toBe('example.com');
        });

        it('parses email with name without quotes', function () {
            $contact = Contact::parse('John Doe <john@example.com>');

            expect($contact->name)->toBe('John Doe');
            expect($contact->email)->toBe('john@example.com');
            expect($contact->domain)->toBe('example.com');
        });

        it('parses email only in brackets', function () {
            $contact = Contact::parse('<john@example.com>');

            expect($contact->name)->toBeNull();
            expect($contact->email)->toBe('john@example.com');
            expect($contact->domain)->toBe('example.com');
        });

        it('parses plain email address', function () {
            $contact = Contact::parse('john@example.com');

            expect($contact->name)->toBeNull();
            expect($contact->email)->toBe('john@example.com');
            expect($contact->domain)->toBe('example.com');
        });

        it('handles complex names with special characters', function () {
            $contact = Contact::parse('"Smith, John Jr." <john.smith@company.co.uk>');

            expect($contact->name)->toBe('Smith, John Jr.');
            expect($contact->email)->toBe('john.smith@company.co.uk');
            expect($contact->domain)->toBe('company.co.uk');
        });

        it('handles names with quotes that need escaping', function () {
            $contact = Contact::parse('"John \"Johnny\" Doe" <john@example.com>');

            expect($contact->name)->toBe('John "Johnny" Doe');
            expect($contact->email)->toBe('john@example.com');
        });

        it('handles malformed email gracefully', function () {
            $contact = Contact::parse('not-an-email');

            expect($contact->email)->toBe('not-an-email');
            expect($contact->name)->toBeNull();
            expect($contact->domain)->toBeNull();
        });

        it('trims whitespace', function () {
            $contact = Contact::parse('  John Doe  <  john@example.com  >  ');

            expect($contact->name)->toBe('John Doe');
            expect($contact->email)->toBe('john@example.com');
        });
    });

    describe('parseMultiple method', function () {
        it('parses multiple emails correctly', function () {
            $contacts = Contact::parseMultiple('john@example.com, "Jane Doe" <jane@example.com>, <admin@example.com>');

            expect($contacts)->toHaveCount(3);

            expect($contacts[0]->email)->toBe('john@example.com');
            expect($contacts[0]->name)->toBeNull();

            expect($contacts[1]->email)->toBe('jane@example.com');
            expect($contacts[1]->name)->toBe('Jane Doe');

            expect($contacts[2]->email)->toBe('admin@example.com');
            expect($contacts[2]->name)->toBeNull();
        });

        it('handles commas in quoted names', function () {
            $contacts = Contact::parseMultiple('"Smith, John" <john@example.com>, "Doe, Jane" <jane@example.com>');

            expect($contacts)->toHaveCount(2);
            expect($contacts[0]->name)->toBe('Smith, John');
            expect($contacts[1]->name)->toBe('Doe, Jane');
        });

        it('handles empty string', function () {
            $contacts = Contact::parseMultiple('');
            expect($contacts)->toHaveCount(0);
        });

        it('handles single email', function () {
            $contacts = Contact::parseMultiple('john@example.com');

            expect($contacts)->toHaveCount(1);
            expect($contacts[0]->email)->toBe('john@example.com');
        });

        it('handles emails with angle brackets and commas', function () {
            $contacts = Contact::parseMultiple('User One <user1@example.com>, User Two <user2@example.com>');

            expect($contacts)->toHaveCount(2);
            expect($contacts[0]->name)->toBe('User One');
            expect($contacts[0]->email)->toBe('user1@example.com');
            expect($contacts[1]->name)->toBe('User Two');
            expect($contacts[1]->email)->toBe('user2@example.com');
        });
    });

    describe('domain methods', function () {
        it('extracts domain correctly', function () {
            $contact = new Contact('john@example.com');
            expect($contact->domain)->toBe('example.com');
        });

        it('handles subdomain', function () {
            $contact = new Contact('admin@mail.company.co.uk');
            expect($contact->domain)->toBe('mail.company.co.uk');
        });

        it('handles email without domain', function () {
            $contact = new Contact('invalid-email');
            expect($contact->domain)->toBeNull();
        });

        it('checks if contact is from domain', function () {
            $contact = new Contact('john@example.com');

            expect($contact->isFromDomain('example.com'))->toBeTrue();
            expect($contact->isFromDomain('EXAMPLE.COM'))->toBeTrue(); // Case insensitive
            expect($contact->isFromDomain('other.com'))->toBeFalse();
        });
    });

    describe('utility methods', function () {
        it('gets display name when name is present', function () {
            $contact = new Contact('john@example.com', 'John Doe');
            expect($contact->getDisplayName())->toBe('John Doe');
        });

        it('gets email as display name when name is absent', function () {
            $contact = new Contact('john@example.com');
            expect($contact->getDisplayName())->toBe('john@example.com');
        });

        it('gets local part of email', function () {
            $contact = new Contact('john.doe+tag@example.com');
            expect($contact->getLocalPart())->toBe('john.doe+tag');
        });

        it('returns null for local part when no @ symbol', function () {
            $contact = new Contact('invalid-email');
            expect($contact->getLocalPart())->toBeNull();
        });
    });

    describe('formatting', function () {
        it('formats contact with name using brackets', function () {
            $contact = new Contact('john@example.com', 'John Doe');
            expect($contact->format())->toBe('John Doe <john@example.com>');
        });

        it('formats contact with special characters in name using quotes', function () {
            $contact = new Contact('john@example.com', 'Smith, John Jr.');
            expect($contact->format())->toBe('"Smith, John Jr." <john@example.com>');
        });

        it('formats contact without name', function () {
            $contact = new Contact('john@example.com');
            expect($contact->format())->toBe('john@example.com');
        });

        it('converts to string using format method', function () {
            $contact = new Contact('john@example.com', 'John Doe');
            expect((string) $contact)->toBe('John Doe <john@example.com>');
        });
    });

    describe('array conversion', function () {
        it('converts to array with all properties', function () {
            $contact = new Contact('john.doe@example.com', 'John Doe');
            $array = $contact->toArray();

            expect($array)->toBe([
                'email' => 'john.doe@example.com',
                'name' => 'John Doe',
                'domain' => 'example.com',
                'display_name' => 'John Doe',
                'local_part' => 'john.doe',
            ]);
        });

        it('handles contact without name', function () {
            $contact = new Contact('admin@example.com');
            $array = $contact->toArray();

            expect($array['name'])->toBeNull();
            expect($array['display_name'])->toBe('admin@example.com');
        });
    });
});
