<?php

namespace PartridgeRocks\GmailClient;

use PartridgeRocks\GmailClient\Commands\GmailClientCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GmailClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('gmail-client')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_gmail_client_table')
            ->hasCommand(GmailClientCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GmailClient::class, function ($app) {
            $client = new GmailClient;

            // If we have a token in session/config, authenticate the client
            $token = session('gmail_access_token') ?? config('gmail-client.access_token');
            if ($token) {
                $client->authenticate($token);
            }

            return $client;
        });
    }

    public function packageBooted(): void
    {
        // Register view components or other boot-time functionality
        if (config('gmail-client.branded_template') && file_exists(config('gmail-client.branded_template'))) {
            $this->loadViewsFrom(config('gmail-client.branded_template'), 'gmail-client');
        }

        // Register routes for authentication
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes()
    {
        // Only register routes if the configuration indicates to do so
        if (config('gmail-client.register_routes', false)) {
            $this->app['router']->group([
                'prefix' => config('gmail-client.route_prefix', 'gmail'),
                'middleware' => config('gmail-client.route_middleware', ['web']),
            ], function ($router) {
                $router->get('auth/redirect', [\PartridgeRocks\GmailClient\Http\Controllers\GmailAuthController::class, 'redirect'])
                    ->name('gmail.auth.redirect');

                $router->get('auth/callback', [\PartridgeRocks\GmailClient\Http\Controllers\GmailAuthController::class, 'callback'])
                    ->name('gmail.auth.callback');
            });
        }
    }
}
