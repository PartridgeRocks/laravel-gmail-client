<?php

use PartridgeRocks\GmailClient\Data\Errors\AuthenticationErrorDTO;

describe('AuthenticationErrorDTO', function () {
    it('has correct error type constants defined', function () {
        expect(AuthenticationErrorDTO::INVALID_TOKEN)->toBe('invalid_token');
        expect(AuthenticationErrorDTO::MISSING_TOKEN)->toBe('missing_token');
        expect(AuthenticationErrorDTO::REFRESH_FAILED)->toBe('refresh_failed');
        expect(AuthenticationErrorDTO::TOKEN_EXPIRED)->toBe('token_expired');
        expect(AuthenticationErrorDTO::UNAUTHORIZED)->toBe('unauthorized');
        expect(AuthenticationErrorDTO::OAUTH_ERROR)->toBe('oauth_error');
    });

    it('can create from OAuth error type', function () {
        $dto = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::OAUTH_ERROR,
            'OAuth flow failed',
            ['client_id' => 'test_client']
        );

        expect($dto->code)->toBe('oauth_error');
        expect($dto->message)->toBe('OAuth authentication process failed');
        expect($dto->detail)->toBe('OAuth flow failed');
        expect($dto->context)->toBe(['client_id' => 'test_client']);
        expect($dto->service)->toBe('Gmail API');
    });

    it('can create from invalid token type', function () {
        $dto = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::INVALID_TOKEN);

        expect($dto->code)->toBe('invalid_token');
        expect($dto->message)->toBe('The access token is invalid or has expired');
        expect($dto->detail)->toBeNull();
        expect($dto->context)->toBeNull();
    });

    it('can create from missing token type', function () {
        $dto = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::MISSING_TOKEN);

        expect($dto->code)->toBe('missing_token');
        expect($dto->message)->toBe('No access token was provided for authentication');
    });

    it('can create from refresh failed type with detail', function () {
        $dto = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::REFRESH_FAILED,
            'Refresh token expired'
        );

        expect($dto->code)->toBe('refresh_failed');
        expect($dto->message)->toBe('Failed to refresh the access token');
        expect($dto->detail)->toBe('Refresh token expired');
    });

    it('can create from token expired type', function () {
        $dto = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::TOKEN_EXPIRED);

        expect($dto->code)->toBe('token_expired');
        expect($dto->message)->toBe('The access token has expired');
    });

    it('can create from unauthorized type', function () {
        $dto = AuthenticationErrorDTO::fromType(AuthenticationErrorDTO::UNAUTHORIZED);

        expect($dto->code)->toBe('unauthorized');
        expect($dto->message)->toBe('Unauthorized access to the requested resource');
    });

    it('falls back to generic message for unknown error type', function () {
        $dto = AuthenticationErrorDTO::fromType('unknown_error_type');

        expect($dto->code)->toBe('unknown_error_type');
        expect($dto->message)->toBe('Authentication error');
    });

    it('can include authentication source in context', function () {
        $dto = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::OAUTH_ERROR,
            'OAuth failed',
            ['auth_source' => 'google_oauth']
        );

        expect($dto->authenticationSource)->toBe('google_oauth');
        expect($dto->context['auth_source'])->toBe('google_oauth');
    });

    it('handles null authentication source gracefully', function () {
        $dto = AuthenticationErrorDTO::fromType(
            AuthenticationErrorDTO::OAUTH_ERROR,
            'OAuth failed',
            ['other_data' => 'value']
        );

        expect($dto->authenticationSource)->toBeNull();
        expect($dto->context['other_data'])->toBe('value');
    });

    it('can create DTO with custom service name', function () {
        $dto = new AuthenticationErrorDTO(
            code: 'custom_error',
            message: 'Custom error message',
            detail: 'Error details',
            context: ['key' => 'value'],
            service: 'Custom Service',
            authenticationSource: 'custom_auth'
        );

        expect($dto->code)->toBe('custom_error');
        expect($dto->message)->toBe('Custom error message');
        expect($dto->detail)->toBe('Error details');
        expect($dto->context)->toBe(['key' => 'value']);
        expect($dto->service)->toBe('Custom Service');
        expect($dto->authenticationSource)->toBe('custom_auth');
    });

    it('defaults to Gmail API service when not specified', function () {
        $dto = new AuthenticationErrorDTO(
            code: 'test_error',
            message: 'Test message'
        );

        expect($dto->service)->toBe('Gmail API');
    });
});
