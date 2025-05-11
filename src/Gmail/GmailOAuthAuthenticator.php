<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\OAuthAuthenticator;

class GmailOAuthAuthenticator implements OAuthAuthenticator
{
    /**
     * Create a new Gmail OAuth authenticator.
     */
    public function __construct(
        protected string $accessToken,
        protected ?string $refreshToken = null,
        protected ?string $tokenType = 'Bearer',
        protected ?\DateTimeInterface $expiresAt = null
    ) {}

    /**
     * Get the access token.
     */
    public function getToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the refresh token.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Get the token type.
     */
    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    /**
     * Get the expiry date of the token.
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * Check if the token has expired.
     */
    public function hasExpired(): bool
    {
        if (! $this->expiresAt) {
            return false;
        }

        return $this->expiresAt <= new \DateTime;
    }
}
