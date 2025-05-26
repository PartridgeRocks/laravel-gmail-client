<?php

use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ModifyMessageLabelsRequest;
use Saloon\Enums\Method;

describe('ModifyMessageLabelsRequest', function () {
    it('can create request with message ID and label modifications', function () {
        $request = new ModifyMessageLabelsRequest(
            'message123',
            ['STARRED', 'INBOX'],
            ['UNREAD']
        );

        expect($request)->toBeInstanceOf(ModifyMessageLabelsRequest::class);
    });

    it('resolves to correct Gmail API endpoint', function () {
        $request = new ModifyMessageLabelsRequest('message123');

        expect($request->resolveEndpoint())->toBe('/users/me/messages/message123/modify');
    });

    it('uses POST method for Gmail API modify endpoint', function () {
        $reflection = new ReflectionClass(ModifyMessageLabelsRequest::class);
        $property = $reflection->getProperty('method');
        $property->setAccessible(true);

        $request = new ModifyMessageLabelsRequest('message123');
        $method = $property->getValue($request);

        expect($method)->toBe(Method::POST);
    });

    it('includes addLabelIds in request body when provided', function () {
        $request = new ModifyMessageLabelsRequest(
            'message123',
            ['STARRED', 'IMPORTANT']
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('addLabelIds');
        expect($body['addLabelIds'])->toBe(['STARRED', 'IMPORTANT']);
        expect($body)->not->toHaveKey('removeLabelIds');
    });

    it('includes removeLabelIds in request body when provided', function () {
        $request = new ModifyMessageLabelsRequest(
            'message123',
            [],
            ['UNREAD', 'SPAM']
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('removeLabelIds');
        expect($body['removeLabelIds'])->toBe(['UNREAD', 'SPAM']);
        expect($body)->not->toHaveKey('addLabelIds');
    });

    it('includes both add and remove labels when provided', function () {
        $request = new ModifyMessageLabelsRequest(
            'message123',
            ['STARRED'],
            ['UNREAD']
        );

        $body = $request->defaultBody();

        expect($body)->toHaveKey('addLabelIds');
        expect($body)->toHaveKey('removeLabelIds');
        expect($body['addLabelIds'])->toBe(['STARRED']);
        expect($body['removeLabelIds'])->toBe(['UNREAD']);
    });

    it('creates empty body when no labels provided', function () {
        $request = new ModifyMessageLabelsRequest('message123');

        $body = $request->defaultBody();

        expect($body)->toBe([]);
    });

    it('handles custom label IDs correctly', function () {
        $customLabels = ['Label_1', 'Label_2'];
        $request = new ModifyMessageLabelsRequest(
            'message123',
            $customLabels
        );

        $body = $request->defaultBody();

        expect($body['addLabelIds'])->toBe($customLabels);
    });

    it('can handle long message IDs', function () {
        $longMessageId = str_repeat('a', 100);
        $request = new ModifyMessageLabelsRequest($longMessageId);

        expect($request->resolveEndpoint())->toBe("/users/me/messages/{$longMessageId}/modify");
    });
});
