<?php

namespace PartridgeRocks\GmailClient\Tests\Facades;

use PartridgeRocks\GmailClient\Facades\GmailClient;
use PartridgeRocks\GmailClient\Tests\TestCase;

class GmailClientFacadeTest extends TestCase
{
    public function test_facade_can_be_resolved()
    {
        $this->assertInstanceOf(
            \PartridgeRocks\GmailClient\GmailClient::class,
            GmailClient::getFacadeRoot()
        );
    }
}
