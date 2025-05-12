<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\OAuthAuthenticator;

class GmailOAuthAuthenticator implements OAuthAuthenticator
{
    /**
     * Get the access token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Check if the token has not expired.
     */
    public function hasNotExpired(): bool
    {
        return ! $this->hasExpired();
    }

    /**
     * Check if the token is refreshable.
     */
    public function isRefreshable(): bool
    {
        return $this->refreshToken !== null;
    }

    /**
     * Check if the token is not refreshable.
     */
    public function isNotRefreshable(): bool
    {
        return ! $this->isRefreshable();
    }

    /**
     * Set a new access token.
     */
    public function set(string $accessToken, ?string $refreshToken = null, ?string $tokenType = null, ?\DateTimeInterface $expiresAt = null): static
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->tokenType = $tokenType;
        $this->expiresAt = $expiresAt;
    }

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
    public function getExpiresAt(): ?DateTimeImmutable
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
