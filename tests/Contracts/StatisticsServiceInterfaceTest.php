<?php

use PartridgeRocks\GmailClient\Contracts\StatisticsServiceInterface;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Services\StatisticsService;

describe('StatisticsServiceInterface Contract', function () {
    it('can be resolved from Laravel container', function () {
        $service = app(StatisticsServiceInterface::class);

        expect($service)->toBeInstanceOf(StatisticsServiceInterface::class);
        expect($service)->toBeInstanceOf(StatisticsService::class);
    });

    it('returns same instance when bound as singleton', function () {
        $service1 = app(StatisticsServiceInterface::class);
        $service2 = app(StatisticsServiceInterface::class);

        expect($service1 === $service2)->toBeTrue();
    });

    it('has proper dependency injection for connector', function () {
        $service = app(StatisticsServiceInterface::class);

        // Verify service can be constructed (dependencies resolved)
        expect($service)->toBeInstanceOf(StatisticsService::class);
    });

    it('can be swapped with custom implementation', function () {
        // Create mock implementation
        $mockService = new class implements StatisticsServiceInterface
        {
            public function getAccountStatistics(array $options = []): array
            {
                return ['test' => 'mock'];
            }

            public function getAccountHealth(): array
            {
                return ['status' => 'mock'];
            }

            public function safeGetAccountStatistics(array $options = []): array
            {
                return ['safe' => 'mock'];
            }

            public function isConnected(): bool
            {
                return true;
            }

            public function getAccountSummary(): array
            {
                return ['summary' => 'mock'];
            }
        };

        // Bind custom implementation
        app()->singleton(StatisticsServiceInterface::class, fn () => $mockService);

        $service = app(StatisticsServiceInterface::class);

        expect($service->getAccountStatistics())->toBe(['test' => 'mock']);
        expect($service->getAccountHealth())->toBe(['status' => 'mock']);
        expect($service->safeGetAccountStatistics())->toBe(['safe' => 'mock']);
        expect($service->isConnected())->toBeTrue();
        expect($service->getAccountSummary())->toBe(['summary' => 'mock']);
    });

    it('interface defines all required methods with correct signatures', function () {
        $reflection = new ReflectionClass(StatisticsServiceInterface::class);
        $methods = $reflection->getMethods();

        $expectedMethods = [
            'getAccountStatistics',
            'getAccountHealth',
            'safeGetAccountStatistics',
            'isConnected',
            'getAccountSummary',
        ];

        expect($methods)->toHaveCount(5);

        foreach ($expectedMethods as $methodName) {
            $method = $reflection->getMethod($methodName);

            expect($method->getName())->toBe($methodName);
            expect($method->isPublic())->toBeTrue();

            // Check return types
            if ($methodName === 'isConnected') {
                expect($method->getReturnType()?->getName())->toBe('bool');
            } else {
                expect($method->getReturnType()?->getName())->toBe('array');
            }
        }
    });

    it('concrete implementation properly implements interface', function () {
        $service = new StatisticsService(new GmailConnector);

        expect($service)->toBeInstanceOf(StatisticsServiceInterface::class);

        // Verify all interface methods are implemented
        $interfaceMethods = get_class_methods(StatisticsServiceInterface::class);
        $serviceMethods = get_class_methods($service);

        foreach ($interfaceMethods as $method) {
            expect($serviceMethods)->toContain($method);
        }
    });
});
