<?php

namespace PartridgeRocks\GmailClient\Constants;

/**
 * Gmail-specific constants for labels, queries, and API values.
 */
final class GmailConstants
{
    /**
     * Gmail system label IDs.
     */
    public const LABEL_INBOX = 'INBOX';
    public const LABEL_STARRED = 'STARRED';
    public const LABEL_UNREAD = 'UNREAD';
    public const LABEL_SENT = 'SENT';
    public const LABEL_DRAFT = 'DRAFT';
    public const LABEL_TRASH = 'TRASH';
    public const LABEL_SPAM = 'SPAM';
    public const LABEL_IMPORTANT = 'IMPORTANT';

    /**
     * Gmail search query terms.
     */
    public const QUERY_UNREAD = 'is:unread';
    public const QUERY_STARRED = 'is:starred';
    public const QUERY_IMPORTANT = 'is:important';
    public const QUERY_IN_INBOX = 'in:inbox';
    public const QUERY_IN_SENT = 'in:sent';

    /**
     * Label visibility settings.
     */
    public const VISIBILITY_SHOW = 'show';
    public const VISIBILITY_HIDE = 'hide';
    public const LABEL_LIST_VISIBILITY_SHOW = 'labelShow';
    public const LABEL_LIST_VISIBILITY_HIDE = 'labelHide';

    /**
     * Default pagination settings.
     */
    public const DEFAULT_MAX_RESULTS = 100;
    public const MIN_MAX_RESULTS = 1;
    public const MAX_MAX_RESULTS = 500;

    /**
     * MIME types for email content.
     */
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_HTML = 'text/html; charset=utf-8';
    public const CONTENT_TYPE_PLAIN = 'text/plain; charset=utf-8';
    public const CONTENT_TYPE_MULTIPART = 'multipart/mixed';

    /**
     * OAuth and authentication constants.
     */
    public const OAUTH_ACCESS_TOKEN = 'access_token';
    public const OAUTH_REFRESH_TOKEN = 'refresh_token';
    public const OAUTH_EXPIRES_IN = 'expires_in';
    public const OAUTH_GRANT_TYPE_AUTH_CODE = 'authorization_code';
    public const OAUTH_GRANT_TYPE_REFRESH = 'refresh_token';
}