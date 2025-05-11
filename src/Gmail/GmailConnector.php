<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Contracts\OAuthAuthenticator;

class GmailConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the Gmail API.
     *
     * @var string
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
     * @param \Saloon\Contracts\OAuthAuthenticator $authenticator
     * @return $this
     */
    public function authenticate(OAuthAuthenticator $authenticator): self
    {
        $this->withTokenAuth($authenticator->getToken());

        return $this;
    }
}