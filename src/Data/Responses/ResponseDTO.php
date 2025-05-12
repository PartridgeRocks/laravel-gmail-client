<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

abstract class ResponseDTO extends Data
{
    public function __construct(
        public readonly ?string $etag = null,
        public readonly ?Carbon $responseTime = null
    ) {}

    /**
     * Create a response DTO from an API response
     *
     * @param  array  $response  The API response data
     */
    abstract public static function fromApiResponse(array $response): static;
}
