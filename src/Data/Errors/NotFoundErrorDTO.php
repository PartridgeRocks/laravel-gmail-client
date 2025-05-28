<?php

namespace PartridgeRocks\GmailClient\Data\Errors;

class NotFoundErrorDTO extends ErrorDTO
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $detail = null,
        /** @var array<string, mixed>|null */
        public ?array $context = null,
        public ?string $service = 'Gmail API',
        public ?string $resourceType = null,
        public ?string $resourceId = null
    ) {
        parent::__construct($code, $message, $detail, $context, $service);
    }

    /**
     * Create a not found error for a specific resource
     */
    /**
     * @param array<string, mixed>|null $context
     */
    public static function forResource(string $resourceType, string $resourceId, ?string $detail = null, ?array $context = null): self
    {
        return new self(
            code: 'not_found',
            message: "{$resourceType} with ID '{$resourceId}' not found",
            detail: $detail,
            context: $context,
            resourceType: $resourceType,
            resourceId: $resourceId
        );
    }
}
