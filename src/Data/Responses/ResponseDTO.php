<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Spatie\LaravelData\Data;
use Illuminate\Support\Carbon;

abstract class ResponseDTO extends Data
{
    public function __construct(
        public readonly ?string $etag = null,
        public readonly ?Carbon $responseTime = null
    ) {
    }

    /**
     * Create a response DTO from an API response
     * 
     * @param array $response The API response data
     * @return static
     */
    abstract public static function fromApiResponse(array $response): static;
}