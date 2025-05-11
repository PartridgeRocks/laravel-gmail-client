<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\OAuthAuthenticator;

class GmailOAuthAuthenticator implements OAuthAuthenticator
{
    /**
     * Create a new Gmail OAuth authenticator.
     *
     * @param string $accessToken
     * @param string|null $refreshToken
     * @param string|null $tokenType
     * @param \DateTimeInterface|null $expiresAt
     */
    public function __construct(
        protected string $accessToken,
        protected ?string $refreshToken = null,
        protected ?string $tokenType = 'Bearer',
        protected ?\DateTimeInterface $expiresAt = null
    ) {
    }

    /**
     * Get the access token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the refresh token.
     *
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Get the token type.
     *
     * @return string|null
     */
    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    /**
     * Get the expiry date of the token.
     *
     * @return \DateTimeInterface|null
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * Check if the token has expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        if (! $this->expiresAt) {
            return false;
        }

        return $this->expiresAt <= new \DateTime();
    }
}