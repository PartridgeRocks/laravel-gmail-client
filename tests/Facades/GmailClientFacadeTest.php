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

    public function test_facade_can_invoke_methods()
    {
        // Mock the underlying client
        $mockClient = $this->mock(\PartridgeRocks\GmailClient\GmailClient::class);
        $mockClient->shouldReceive('authenticate')->once()->with('test-token')->andReturnSelf();

        // Test the facade method call
        GmailClient::authenticate('test-token');
    }
}
