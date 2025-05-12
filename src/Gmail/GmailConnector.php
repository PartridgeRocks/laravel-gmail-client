<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\Authenticator;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class GmailConnector extends Connector
{
    use AcceptsJson;
    use AuthorizationCodeGrant;

    /**
     * The Base URL of the Gmail API.
     *
     * @return string
     */
    public function resolveBaseUrl(): string
    {
        return 'https://gmail.googleapis.com/gmail/v1';
    }

    /**
     * Default headers for every request.
     *
     * @return string[]
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Default query parameters for every request.
     *
     * @return string[]
     */
    protected function defaultQuery(): array
    {
        return [];
    }

    /**
     * Default OAuth configuration.
     *
     * Configures the OAuth settings for the Gmail API, including:
     * - Client ID and secret from config
     * - Default scopes from config (or fallback scopes)
     * - Google's OAuth endpoints
     * - Redirect URI from config
     * - Google-specific parameters like offline access and consent prompt
     *
     * @return \Saloon\Helpers\OAuth2\OAuthConfig
     */
    protected function defaultOauthConfig(): OAuthConfig
    {
        // In testing or when config is not set, use test values
        $clientId = config('gmail-client.client_id');
        $clientSecret = config('gmail-client.client_secret');
        $redirectUri = config('gmail-client.redirect_uri');

        if (app()->environment('testing') || empty($clientId)) {
            $clientId = 'test-client-id';
            $clientSecret = 'test-client-secret';
            $redirectUri = 'https://example.com/callback';
        }

        // Default scopes if none provided in config
        $defaultScopes = [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/gmail.compose',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/gmail.labels',
        ];

        $scopes = config('gmail-client.scopes', $defaultScopes);

        return OAuthConfig::make()
            ->setClientId($clientId)
            ->setClientSecret($clientSecret)
            ->setDefaultScopes($scopes)
            ->setAuthorizeEndpoint('https://accounts.google.com/o/oauth2/v2/auth')
            ->setTokenEndpoint('https://oauth2.googleapis.com/token')
            ->setUserEndpoint('/users/me/profile')
            ->setRedirectUri($redirectUri)
            ->setRequestModifier(function (Request $request) {
                // Grant Google-specific parameters for authorization requests
                if ($request->getMethod()->value === 'GET' && str_contains($request->resolveEndpoint(), 'accounts.google.com')) {
                    $request->query()->merge([
                        'access_type' => 'offline',
                        'prompt' => 'consent'
                    ]);
                }
            });
    }

    /**
     * Set the authenticator.
     *
     * @param \Saloon\Contracts\Authenticator $authenticator
     * @return static
     */
    public function authenticate(Authenticator $authenticator): static
    {
        if ($authenticator instanceof OAuthAuthenticator) {
            $this->withTokenAuth($authenticator->getToken());
        } else {
            // Fall back to parent implementation for other authenticator types
            parent::authenticate($authenticator);
        }

        return $this;
    }
}
