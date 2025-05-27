<?php

namespace PartridgeRocks\GmailClient\Constants;

/**
 * HTTP status codes used throughout the Gmail Client.
 */
final class HttpStatus
{
    /**
     * Success status codes.
     */
    public const OK = 200;
    public const CREATED = 201;
    public const NO_CONTENT = 204;

    /**
     * Client error status codes.
     */
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const CONFLICT = 409;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    /**
     * Server error status codes.
     */
    public const INTERNAL_SERVER_ERROR = 500;
    public const SERVICE_UNAVAILABLE = 503;
}