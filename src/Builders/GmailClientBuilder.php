<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Builders;

use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;
use PartridgeRocks\GmailClient\Contracts\StatisticsServiceInterface;
use PartridgeRocks\GmailClient\Factories\GmailServiceFactory;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\GmailClient;

class GmailClientBuilder
{
    private ?string $accessToken = null;
    private ?GmailConnector $connector = null;
    private ?AuthServiceInterface $authService = null;
    private ?LabelServiceInterface $labelService = null;
    private ?MessageServiceInterface $messageService = null;
    private ?StatisticsServiceInterface $statisticsService = null;

    public static function create(): self
    {
        return new self;
    }

    public function withToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function withConnector(GmailConnector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    public function withAuthService(AuthServiceInterface $authService): self
    {
        $this->authService = $authService;

        return $this;
    }

    public function withLabelService(LabelServiceInterface $labelService): self
    {
        $this->labelService = $labelService;

        return $this;
    }

    public function withMessageService(MessageServiceInterface $messageService): self
    {
        $this->messageService = $messageService;

        return $this;
    }

    public function withStatisticsService(StatisticsServiceInterface $statisticsService): self
    {
        $this->statisticsService = $statisticsService;

        return $this;
    }

    public function build(): GmailClient
    {
        $connector = $this->connector ?? new GmailConnector;
        $factory = new GmailServiceFactory($connector);

        return new GmailClient(
            $this->accessToken,
            $this->authService ?? $factory->createAuthService(),
            $this->labelService ?? $factory->createLabelService(),
            $this->messageService ?? $factory->createMessageService(),
            $this->statisticsService ?? $factory->createStatisticsService(),
            $connector
        );
    }

    public function buildWithDefaults(): GmailClient
    {
        $connector = new GmailConnector;
        $factory = new GmailServiceFactory($connector);

        return new GmailClient(
            $this->accessToken,
            $factory->createAuthService(),
            $factory->createLabelService(),
            $factory->createMessageService(),
            $factory->createStatisticsService(),
            $connector
        );
    }
}
