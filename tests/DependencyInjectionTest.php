<?php

use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;
use PartridgeRocks\GmailClient\GmailClient;
use PartridgeRocks\GmailClient\Services\AuthService;
use PartridgeRocks\GmailClient\Services\LabelService;
use PartridgeRocks\GmailClient\Services\MessageService;

describe('Dependency Injection', function () {
    it('can resolve service interfaces from container', function () {
        $authService = app(AuthServiceInterface::class);
        $labelService = app(LabelServiceInterface::class);
        $messageService = app(MessageServiceInterface::class);

        expect($authService)->toBeInstanceOf(AuthService::class);
        expect($labelService)->toBeInstanceOf(LabelService::class);
        expect($messageService)->toBeInstanceOf(MessageService::class);
    });

    it('can resolve concrete service implementations from container', function () {
        $authService = app(AuthService::class);
        $labelService = app(LabelService::class);
        $messageService = app(MessageService::class);

        expect($authService)->toBeInstanceOf(AuthService::class);
        expect($labelService)->toBeInstanceOf(LabelService::class);
        expect($messageService)->toBeInstanceOf(MessageService::class);
    });

    it('returns same instance for singletons', function () {
        $authService1 = app(AuthServiceInterface::class);
        $authService2 = app(AuthServiceInterface::class);

        expect($authService1)->toBe($authService2);

        $labelService1 = app(LabelServiceInterface::class);
        $labelService2 = app(LabelServiceInterface::class);

        expect($labelService1)->toBe($labelService2);

        $messageService1 = app(MessageServiceInterface::class);
        $messageService2 = app(MessageServiceInterface::class);

        expect($messageService1)->toBe($messageService2);
    });

    it('can inject services into GmailClient constructor', function () {
        $mockAuthService = Mockery::mock(AuthServiceInterface::class);
        $mockLabelService = Mockery::mock(LabelServiceInterface::class);
        $mockMessageService = Mockery::mock(MessageServiceInterface::class);

        $client = new GmailClient(
            null,
            $mockAuthService,
            $mockLabelService,
            $mockMessageService
        );

        expect($client)->toBeInstanceOf(GmailClient::class);
    });

    it('uses default implementations when no services provided', function () {
        $client = new GmailClient;

        // Use reflection to access protected properties
        $reflection = new ReflectionClass($client);

        $authServiceProperty = $reflection->getProperty('authService');
        $authServiceProperty->setAccessible(true);
        $authService = $authServiceProperty->getValue($client);

        $labelServiceProperty = $reflection->getProperty('labelService');
        $labelServiceProperty->setAccessible(true);
        $labelService = $labelServiceProperty->getValue($client);

        $messageServiceProperty = $reflection->getProperty('messageService');
        $messageServiceProperty->setAccessible(true);
        $messageService = $messageServiceProperty->getValue($client);

        expect($authService)->toBeInstanceOf(AuthService::class);
        expect($labelService)->toBeInstanceOf(LabelService::class);
        expect($messageService)->toBeInstanceOf(MessageService::class);
    });

    it('can swap service implementations for testing', function () {
        $mockAuthService = Mockery::mock(AuthServiceInterface::class);
        $mockAuthService->shouldReceive('authenticate')->once();

        $client = new GmailClient(null, $mockAuthService);

        // Test that the mock is called
        $client->authenticate('test-token');
    });

    it('interfaces implement expected methods', function () {
        $authService = app(AuthServiceInterface::class);
        $labelService = app(LabelServiceInterface::class);
        $messageService = app(MessageServiceInterface::class);

        // Verify AuthService interface methods
        expect(method_exists($authService, 'authenticate'))->toBeTrue();
        expect(method_exists($authService, 'getAuthorizationUrl'))->toBeTrue();
        expect(method_exists($authService, 'exchangeCode'))->toBeTrue();
        expect(method_exists($authService, 'refreshToken'))->toBeTrue();

        // Verify LabelService interface methods
        expect(method_exists($labelService, 'listLabels'))->toBeTrue();
        expect(method_exists($labelService, 'getLabel'))->toBeTrue();
        expect(method_exists($labelService, 'createLabel'))->toBeTrue();
        expect(method_exists($labelService, 'updateLabel'))->toBeTrue();
        expect(method_exists($labelService, 'deleteLabel'))->toBeTrue();
        expect(method_exists($labelService, 'safeListLabels'))->toBeTrue();

        // Verify MessageService interface methods
        expect(method_exists($messageService, 'listMessages'))->toBeTrue();
        expect(method_exists($messageService, 'getMessage'))->toBeTrue();
        expect(method_exists($messageService, 'sendEmail'))->toBeTrue();
        expect(method_exists($messageService, 'addLabelsToMessage'))->toBeTrue();
        expect(method_exists($messageService, 'removeLabelsFromMessage'))->toBeTrue();
        expect(method_exists($messageService, 'modifyMessageLabels'))->toBeTrue();
        expect(method_exists($messageService, 'safeGetMessage'))->toBeTrue();
        expect(method_exists($messageService, 'safeListMessages'))->toBeTrue();
    });
});
