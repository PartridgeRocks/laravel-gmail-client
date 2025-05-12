<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\Authenticator;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class GmailConnector extends Connector
{
    use AcceptsJson;

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
