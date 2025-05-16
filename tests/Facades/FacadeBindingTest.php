<?php

namespace PartridgeRocks\GmailClient\Tests\Facades;

use Illuminate\Support\Facades\App;
use PartridgeRocks\GmailClient\Facades\GmailClient;
use PartridgeRocks\GmailClient\Tests\TestCase;

class FacadeBindingTest extends TestCase
{
    public function test_facade_binding_exists()
    {
        // Assert that the binding exists in the container
        $this->assertTrue(App::bound('gmail-client'));
    }

    public function test_facade_resolves_to_client_instance()
    {
        // Get the instance from the facade
        $instance = GmailClient::getFacadeRoot();

        // Assert it's the right class
        $this->assertInstanceOf(\PartridgeRocks\GmailClient\GmailClient::class, $instance);
    }
}
