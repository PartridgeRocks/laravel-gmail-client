<?php

namespace PartridgeRocks\GmailClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PartridgeRocks\GmailClient\GmailClient authenticate(string $accessToken)
 * @method static \Illuminate\Support\Collection listMessages(array $query = [])
 * @method static \PartridgeRocks\GmailClient\Data\Email getMessage(string $id)
 * @method static \PartridgeRocks\GmailClient\Data\Email sendEmail(string $to, string $subject, string $body, array $options = [])
 * @method static \Illuminate\Support\Collection listLabels()
 * @method static \PartridgeRocks\GmailClient\Data\Label getLabel(string $id)
 * @method static \PartridgeRocks\GmailClient\Data\Label createLabel(string $name, array $options = [])
 *
 * @see \PartridgeRocks\GmailClient\GmailClient
 */
class GmailClient extends Facade
{
    /**
     * Returns the service container binding key for the underlying Gmail client.
     *
     * @return string The fully qualified class name of the Gmail client service.
     */
    protected static function getFacadeAccessor()
    {
        return 'gmail-client';
    }
}
