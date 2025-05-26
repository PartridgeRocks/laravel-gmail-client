<?php

use PartridgeRocks\GmailClient\Data\Errors\AuthenticationErrorDTO;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;

describe('AuthenticationException', function () {
    it('can create from OAuth error with message', function () {
        $exception = AuthenticationException::fromOAuthError('Invalid client credentials');

        expect($exception)->toBeInstanceOf(AuthenticationException::class);
        expect($exception->getMessage())->toBe('OAuth authentication failed: Invalid client credentials');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBeNull();
    });

    it('can create from OAuth error with exception chaining', function () {
        $originalException = new Exception('OAuth provider error');
        $exception = AuthenticationException::fromOAuthError('Invalid client credentials', $originalException);

        expect($exception->getMessage())->toBe('OAuth authentication failed: Invalid client credentials');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);
    });

    it('includes OAuth context data in error', function () {
        $exception = AuthenticationException::fromOAuthError('Token exchange failed');

        // Get the error DTO using the public method
        $errorDto = $exception->getError();

        expect($errorDto)->toBeInstanceOf(AuthenticationErrorDTO::class);
        expect($errorDto->code)->toBe(AuthenticationErrorDTO::OAUTH_ERROR);
        expect($errorDto->detail)->toBe('Token exchange failed');
        expect($errorDto->context)->toHaveKey('oauth_flow');
        expect($errorDto->context['oauth_flow'])->toBeTrue();
        expect($errorDto->context)->toHaveKey('timestamp');
    });

    it('can create missing token exception with chaining', function () {
        $originalException = new Exception('Token not found');
        $exception = AuthenticationException::missingToken($originalException);

        expect($exception->getMessage())->toBe('No access token was provided for authentication');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);
    });

    it('can create invalid token exception with chaining', function () {
        $originalException = new Exception('Token validation failed');
        $exception = AuthenticationException::invalidToken($originalException);

        expect($exception->getMessage())->toBe('The access token is invalid or has expired');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);
    });

    it('can create token expired exception with chaining', function () {
        $originalException = new Exception('Token expired');
        $exception = AuthenticationException::tokenExpired($originalException);

        expect($exception->getMessage())->toBe('The access token has expired');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);
    });

    it('can create refresh failed exception with detail and chaining', function () {
        $originalException = new Exception('Refresh endpoint error');
        $exception = AuthenticationException::refreshFailed('Refresh token invalid', $originalException);

        expect($exception->getMessage())->toBe('Failed to refresh the access token');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);
    });

    it('can create from response with proper error type detection', function () {
        $response = [
            'error' => [
                'error_description' => 'The access token has expired',
                'error' => 'invalid_token',
            ],
        ];

        $exception = AuthenticationException::fromResponse($response);

        expect($exception->getMessage())->toBe('The access token has expired');
        expect($exception->getCode())->toBe(401);
    });

    it('can create from response with custom message and chaining', function () {
        $originalException = new Exception('API error');
        $response = [
            'error' => [
                'error_description' => 'Access denied for user',
                'error' => 'unauthorized',
            ],
        ];

        $exception = AuthenticationException::fromResponse(
            $response,
            'Custom authentication error',
            $originalException
        );

        // The method uses the DTO's message, but stores the custom message in the detail
        expect($exception->getMessage())->toBe('Unauthorized access to the requested resource');
        expect($exception->getCode())->toBe(401);
        expect($exception->getPrevious())->toBe($originalException);

        // Check that the custom message is stored in the error DTO detail
        $errorDto = $exception->getError();
        expect($errorDto->detail)->toBe('Custom authentication error');
    });

    it('detects token expired from error description', function () {
        $response = [
            'error_description' => 'Token has expired and needs renewal',
        ];

        $exception = AuthenticationException::fromResponse($response);

        // Should detect "expired" in description and use TOKEN_EXPIRED type
        expect($exception->getMessage())->toContain('expired');
    });

    it('detects invalid token from error description', function () {
        $response = [
            'error_description' => 'The provided token is invalid',
        ];

        $exception = AuthenticationException::fromResponse($response);

        // Should detect "invalid" in description and use INVALID_TOKEN type
        expect($exception->getMessage())->toContain('invalid');
    });

    it('falls back to unauthorized for unknown error types', function () {
        $response = [
            'error_description' => 'Some unknown authentication error',
        ];

        $exception = AuthenticationException::fromResponse($response);

        // Should use the default unauthorized message, not the error description
        expect($exception->getMessage())->toBe('Unauthorized access to the requested resource');
        expect($exception->getCode())->toBe(401);

        // The error description should be in the detail
        $errorDto = $exception->getError();
        expect($errorDto->detail)->toBe('Some unknown authentication error');
    });
});
