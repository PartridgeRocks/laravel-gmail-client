<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Factories;

use PartridgeRocks\GmailClient\Contracts\AuthServiceInterface;
use PartridgeRocks\GmailClient\Contracts\LabelServiceInterface;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;
use PartridgeRocks\GmailClient\Contracts\StatisticsServiceInterface;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Services\AuthService;
use PartridgeRocks\GmailClient\Services\LabelService;
use PartridgeRocks\GmailClient\Services\MessageService;
use PartridgeRocks\GmailClient\Services\StatisticsService;

class GmailServiceFactory
{
    public function __construct(
        private readonly GmailConnector $connector
    ) {}

    public function createAuthService(): AuthServiceInterface
    {
        return new AuthService($this->connector);
    }

    public function createLabelService(): LabelServiceInterface
    {
        return new LabelService($this->connector);
    }

    public function createMessageService(): MessageServiceInterface
    {
        return new MessageService($this->connector);
    }

    public function createStatisticsService(): StatisticsServiceInterface
    {
        return new StatisticsService($this->connector);
    }

    public function createAllServices(): array
    {
        return [
            'auth' => $this->createAuthService(),
            'label' => $this->createLabelService(),
            'message' => $this->createMessageService(),
            'statistics' => $this->createStatisticsService(),
        ];
    }
}
