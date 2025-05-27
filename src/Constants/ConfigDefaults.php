<?php

namespace PartridgeRocks\GmailClient\Constants;

/**
 * Default configuration values used throughout the Gmail Client.
 */
final class ConfigDefaults
{
    /**
     * API timeout and retry settings.
     */
    public const API_TIMEOUT_SECONDS = 30;
    public const DEFAULT_RETRY_AFTER_SECONDS = 60;
    public const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Pagination and result limits.
     */
    public const DEFAULT_PAGE_SIZE = 100;
    public const MIN_PAGE_SIZE = 1;
    public const MAX_PAGE_SIZE = 500;
    public const SMALL_PAGE_SIZE = 10;
    public const MEDIUM_PAGE_SIZE = 25;
    public const LARGE_PAGE_SIZE = 50;

    /**
     * Account and message limits.
     */
    public const UNREAD_ESTIMATION_THRESHOLD = 50;
    public const TODAY_MESSAGE_LIMIT = 15;
    public const MAX_SUPPORTED_ACCOUNTS = 5;
    public const MESSAGE_BATCH_SIZE = 25;

    /**
     * Cache and health check intervals.
     */
    public const CACHE_TTL_SECONDS = 300; // 5 minutes
    public const HEALTH_CHECK_INTERVAL_SECONDS = 3600; // 1 hour
    public const TOKEN_REFRESH_BUFFER_SECONDS = 300; // 5 minutes

    /**
     * Email processing settings.
     */
    public const MAX_EMAIL_SIZE_MB = 25;
    public const MAX_ATTACHMENT_SIZE_MB = 10;
    public const DEFAULT_EMAIL_FORMAT = 'full';
    public const MINIMAL_EMAIL_FORMAT = 'minimal';
}
