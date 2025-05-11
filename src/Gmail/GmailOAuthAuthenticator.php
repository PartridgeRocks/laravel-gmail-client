<?php

namespace PartridgeRocks\GmailClient\Gmail;

use Saloon\Contracts\OAuthAuthenticator;

class GmailOAuthAuthenticator implements OAuthAuthenticator
{
    /**
     * Initializes a Gmail OAuth authenticator with access token details.
     *
     * @param string $accessToken The OAuth access token.
     * @param string|null $refreshToken Optional refresh token for obtaining new access tokens.
     * @param string|null $tokenType The type of the token, typically 'Bearer'.
     * @param \DateTimeInterface|null $expiresAt Optional expiration date and time for the access token.
     */
    public function __construct(
        protected string $accessToken,
        protected ?string $refreshToken = null,
        protected ?string $tokenType = 'Bearer',
        protected ?\DateTimeInterface $expiresAt = null
    ) {}

    /**
     * Returns the OAuth access token used for authentication.
     *
     * @return string The access token string.
     */
    public function getToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Returns the refresh token associated with the authenticator, or null if not set.
     *
     * @return string|null The refresh token, or null if unavailable.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /****
     * Returns the OAuth token type used for authentication.
     *
     * @return string|null The token type (e.g., 'Bearer'), or null if not set.
     */
    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    /**
     * Returns the expiration date and time of the access token.
     *
     * @return \DateTimeInterface|null Expiration date of the token, or null if not set.
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    /****
     * Determines whether the access token has expired.
     *
     * Returns false if no expiration date is set; otherwise, returns true if the current time is past the expiration date.
     *
     * @return bool True if the token has expired, false otherwise.
     */
    public function hasExpired(): bool
    {
        if (! $this->expiresAt) {
            return false;
        }

        return $this->expiresAt <= new \DateTime;
    }
}
