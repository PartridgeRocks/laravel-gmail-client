<?php

namespace PartridgeRocks\GmailClient\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use PartridgeRocks\GmailClient\GmailClientServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Sets up the test environment and configures model factory name resolution for the package.
     *
     * Overrides the default factory name guessing to use the package's factory namespace.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'PartridgeRocks\\GmailClient\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * Returns the service providers to be loaded for the test environment.
     *
     * @return array List of service provider class names.
     */
    protected function getPackageProviders($app)
    {
        return [
            GmailClientServiceProvider::class,
        ];
    }

    /**
     * Configures the application environment for testing by setting the default database connection to 'testing'.
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     */
    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_gmail-client_table.php.stub';
        $migration->up();
        */
    }
}
