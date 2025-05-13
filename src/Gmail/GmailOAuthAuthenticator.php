<?php

namespace PartridgeRocks\GmailClient\Gmail;

use DateTimeImmutable;
use DateTimeInterface;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Http\PendingRequest;

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
     *
     * This method is used to update token information
     */
    public function updateToken(string $accessToken, ?string $refreshToken = null, ?string $tokenType = null, ?\DateTimeInterface $expiresAt = null): static
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->tokenType = $tokenType;
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Set authenticator data in a pending request.
     *
     * This method is required by the Saloon Authenticator interface
     */
    public function set(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Authorization', $this->tokenType.' '.$this->accessToken);
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
        // Convert to DateTimeImmutable if it's not already
        if ($this->expiresAt instanceof DateTimeImmutable) {
            return $this->expiresAt;
        } elseif ($this->expiresAt instanceof DateTimeInterface) {
            return new DateTimeImmutable('@'.$this->expiresAt->getTimestamp());
        }

        return null;
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
