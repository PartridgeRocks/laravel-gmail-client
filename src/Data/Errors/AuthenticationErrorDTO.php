<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

class AuthenticationErrorDTO extends ErrorDTO
{
    // Authentication error type constants
    public const INVALID_TOKEN = 'invalid_token';
    public const MISSING_TOKEN = 'missing_token';
    public const REFRESH_FAILED = 'refresh_failed';
    public const TOKEN_EXPIRED = 'token_expired';
    public const UNAUTHORIZED = 'unauthorized';
    public const OAUTH_ERROR = 'oauth_error';

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
            self::INVALID_TOKEN => 'The access token is invalid or has expired',
            self::MISSING_TOKEN => 'No access token was provided for authentication',
            self::REFRESH_FAILED => 'Failed to refresh the access token',
            self::TOKEN_EXPIRED => 'The access token has expired',
            self::UNAUTHORIZED => 'Unauthorized access to the requested resource',
            self::OAUTH_ERROR => 'OAuth authentication process failed',
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
