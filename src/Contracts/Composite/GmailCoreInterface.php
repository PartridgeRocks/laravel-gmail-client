<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

/**
 * Core Gmail interface combining essential read/write operations.
 *
 * This interface provides the fundamental Gmail operations needed for
 * most applications: authentication, reading messages, and basic
 * message/label management.
 */
interface GmailCoreInterface extends GmailOAuthInterface, GmailReadInterface, GmailWriteInterface
{
    // This interface combines core functionality
    // No additional methods needed - composition does the work
}
