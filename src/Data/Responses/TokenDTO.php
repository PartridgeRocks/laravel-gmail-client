<?php

namespace PartridgeRocks\GmailClient\Data\Responses;

use Carbon\Carbon;
use DateTimeInterface;

class TokenDTO extends ResponseDTO
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public ?string $tokenType = 'Bearer',
        public ?int $expiresIn = null,
        public ?Carbon $expiresAt = null,
        /** @var array<string>|null */
        public ?array $scope = null,
        ?string $etag = null,
        ?Carbon $responseTime = null
    ) {
        parent::__construct($etag, $responseTime);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApiResponse(array $data): static
    {
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = Carbon::now()->addSeconds($data['expires_in']);
        }

        return new static(
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'] ?? null,
            tokenType: $data['token_type'] ?? 'Bearer',
            expiresIn: $data['expires_in'] ?? null,
            expiresAt: $expiresAt,
            scope: isset($data['scope']) ? explode(' ', $data['scope']) : null,
            responseTime: Carbon::now()
        );
    }

    /**
     * Check if the token has expired
     */
    public function hasExpired(): bool
    {
        if (! $this->expiresAt) {
            return false;
        }

        return $this->expiresAt->isPast();
    }

    /**
     * Check if token can be refreshed
     */
    public function canRefresh(): bool
    {
        return ! empty($this->refreshToken);
    }

    /**
     * Get expiration time as a DateTime
     */
    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }
}
