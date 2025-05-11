<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class GmailConnector extends Connector
{
    use AcceptsJson;

    /**
     * Returns the base URL for the Gmail API.
     *
     * @return string The Gmail API base endpoint.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://gmail.googleapis.com/gmail/v1';
    }

    /**
     * Returns the default HTTP headers for all requests, specifying JSON content type and acceptance.
     *
     * @return string[] Associative array of HTTP headers.
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Returns the default query parameters for all requests.
     *
     * @return array An empty array, indicating no default query parameters are set.
     */
    protected function defaultQuery(): array
    {
        return [];
    }

    /**
     * Applies OAuth authentication to the connector using the provided authenticator.
     *
     * Retrieves the OAuth token from the given authenticator and configures the connector to use token-based authentication for subsequent requests.
     *
     * @return $this The connector instance for method chaining.
     */
    public function authenticate(OAuthAuthenticator $authenticator): self
    {
        $this->withTokenAuth($authenticator->getToken());

        return $this;
    }
}
