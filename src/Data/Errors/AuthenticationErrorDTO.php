<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

use Spatie\LaravelData\Data;

class AuthenticationErrorDTO extends ErrorDTO
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
        public ?array $context = null,
        public ?string $service = 'Gmail API',
        public ?string $authenticationSource = null
    ) {
        parent::__construct($code, $message, $detail, $context, $service);
    }

    /**
     * Create an authentication error from a specific error type
     */
    public static function fromType(string $type, ?string $detail = null, ?array $context = null): self
    {
        $messages = [
            'invalid_token' => 'The access token is invalid or has expired',
            'missing_token' => 'No access token was provided for authentication',
            'refresh_failed' => 'Failed to refresh the access token',
            'token_expired' => 'The access token has expired',
            'unauthorized' => 'Unauthorized access to the requested resource',
        ];

        return new self(
            code: $type,
            message: $messages[$type] ?? 'Authentication error',
            detail: $detail,
            context: $context,
            authenticationSource: $context['auth_source'] ?? null
        );
    }
}