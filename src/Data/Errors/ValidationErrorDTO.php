<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

class ValidationErrorDTO extends ErrorDTO
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
        /** @var array<string, mixed>|null */
        public ?array $context = null,
        public ?string $service = 'Gmail API',
        /** @var array<string, mixed> */
        public ?array $errors = []
    ) {
        parent::__construct($code, $message, $detail, $context, $service);
    }

    /**
     * Create a validation error for a specific field
     */
    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function forField(string $field, string $message, ?array $context = null): self
    {
        return new self(
            code: 'validation_error',
            message: "Validation failed for field: {$field}",
            detail: $message,
            context: $context,
            errors: [$field => $message]
        );
    }

    /**
     * Create a validation error with multiple field errors
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>|null  $context
     */
    public static function withErrors(array $errors, ?array $context = null): self
    {
        return new self(
            code: 'validation_error',
            message: 'Multiple validation errors occurred',
            detail: 'Please check the errors array for details',
            context: $context,
            errors: $errors
        );
    }

    /**
     * Create a missing required field error
     */
    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function missingRequiredField(string $field, ?array $context = null): self
    {
        return self::forField(
            field: $field,
            message: "The {$field} field is required",
            context: $context
        );
    }
}
