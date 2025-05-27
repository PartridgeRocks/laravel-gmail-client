<?php

namespace PartridgeRocks\GmailClient;

use PartridgeRocks\GmailClient\Commands\GmailClientCommand;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Services\AuthService;
use PartridgeRocks\GmailClient\Services\LabelService;
use PartridgeRocks\GmailClient\Services\MessageService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GmailClientServiceProvider extends PackageServiceProvider
{
    /**
     * Configures the Gmail client package by registering its name, configuration file, views, migration, and console command.
     *
     * @param  Package  $package  The package instance to configure.
     */
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

    /**
     * Registers a singleton GmailClient in the service container, authenticating it with an access token from the session or configuration if available.
     */
    public function packageRegistered(): void
    {
        // Register core Gmail connector
        $this->app->singleton(GmailConnector::class, function ($app) {
            return new GmailConnector;
        });

        // Register individual services
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService($app->make(GmailConnector::class));
        });

        $this->app->singleton(LabelService::class, function ($app) {
            return new LabelService($app->make(GmailConnector::class));
        });

        $this->app->singleton(MessageService::class, function ($app) {
            return new MessageService($app->make(GmailConnector::class));
        });

        // Register main Gmail client
        $this->app->singleton(GmailClient::class, function ($app) {
            $client = new GmailClient;

            // If we have a token in session/config, authenticate the client
            $token = session('gmail_access_token') ?? config('gmail-client.access_token');
            if ($token) {
                $client->authenticate($token);
            }

            return $client;
        });

        // Register binding for the facade
        $this->app->bind('gmail-client', function ($app) {
            return $app->make(GmailClient::class);
        });
    }

    /**
     * Performs boot-time initialization for the Gmail client package.
     *
     * Loads branded views if specified in configuration and registers package routes.
     */
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
     * Registers OAuth authentication routes for the Gmail client package if enabled in configuration.
     *
     * Defines GET routes for authentication redirect and callback under a configurable prefix and middleware group.
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
