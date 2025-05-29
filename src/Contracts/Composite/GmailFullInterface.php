<?php

namespace PartridgeRocks\GmailClient\Contracts\Composite;

/**
 * Complete Gmail interface with all available operations.
 *
 * This interface provides access to all Gmail functionality including
 * core operations, safe methods, and advanced features. This is the
 * main interface that GmailClient implements.
 */
interface GmailFullInterface extends 
    GmailCoreInterface,
    GmailSafeInterface
{
    /**
     * Get the underlying connector for advanced usage.
     */
    public function getConnector(): \PartridgeRocks\GmailClient\Gmail\GmailConnector;
}