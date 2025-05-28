<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

abstract class ResponseDTO extends Data
{
    public function __construct(
        public readonly ?string $etag = null,
        public readonly ?CarbonInterface $responseTime = null
    ) {}

    /**
     * Create a response DTO from an API response
     *
     * @param  array<string, mixed>  $response  The API response data
     */
    abstract public static function fromApiResponse(array $response): static;
}
