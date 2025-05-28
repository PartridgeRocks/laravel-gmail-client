<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

use Spatie\LaravelData\Data;

class ErrorDTO extends Data
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
        /** @var array<string, mixed>|null */
        public ?array $context = null,
        public ?string $service = 'Gmail API'
    ) {}

    /**
     * Create an error DTO from a response array
     */
    /**
     * @param  array<string, mixed>  $errorData
     */
    public static function fromResponse(array $errorData): self
    {
        // Gmail API typically returns errors in the 'error' key
        $error = $errorData['error'] ?? $errorData;

        // Extract commonly used fields in Google API error responses
        $code = $error['code'] ?? $error['status'] ?? 'unknown_error';
        $message = $error['message'] ?? 'Unknown error occurred';
        $detail = $error['details'][0]['detail'] ?? $error['details'][0]['errorMessage'] ?? null;

        return new self(
            code: is_numeric($code) ? (string) $code : $code,
            message: $message,
            detail: $detail,
            context: $errorData,
        );
    }
}
