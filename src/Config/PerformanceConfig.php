<?php

declare(strict_types=1);

namespace PartridgeRocks\GmailClient\Config;

readonly class PerformanceConfig
{
    public function __construct(
        public bool $enableSmartCounting = true,
        public int $countEstimationThreshold = 50,
        public int $defaultCacheTtl = 300,
        public int $maxConcurrentRequests = 10,
        public bool $enableCircuitBreaker = true,
        public int $apiTimeout = 30,
        public bool $enableBatching = true,
        public int $batchSize = 100,
        public int $batchDelayMicroseconds = 100000,
        public bool $enablePagination = true,
        public int $defaultPageSize = 25,
        public int $maxPageSize = 100,
        public bool $enableLazyLoading = true,
        public int $lazyLoadingChunkSize = 50,
        public bool $enableQueryOptimization = true,
        public int $queryOptimizationCacheSize = 1000,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            enableSmartCounting: $config['enable_smart_counting'] ?? true,
            countEstimationThreshold: $config['count_estimation_threshold'] ?? 50,
            defaultCacheTtl: $config['default_cache_ttl'] ?? 300,
            maxConcurrentRequests: $config['max_concurrent_requests'] ?? 10,
            enableCircuitBreaker: $config['enable_circuit_breaker'] ?? true,
            apiTimeout: $config['api_timeout'] ?? 30,
            enableBatching: $config['enable_batching'] ?? true,
            batchSize: $config['batch_size'] ?? 100,
            batchDelayMicroseconds: $config['batch_delay_microseconds'] ?? 100000,
            enablePagination: $config['enable_pagination'] ?? true,
            defaultPageSize: $config['default_page_size'] ?? 25,
            maxPageSize: $config['max_page_size'] ?? 100,
            enableLazyLoading: $config['enable_lazy_loading'] ?? true,
            lazyLoadingChunkSize: $config['lazy_loading_chunk_size'] ?? 50,
            enableQueryOptimization: $config['enable_query_optimization'] ?? true,
            queryOptimizationCacheSize: $config['query_optimization_cache_size'] ?? 1000,
        );
    }

    public function toArray(): array
    {
        return [
            'enable_smart_counting' => $this->enableSmartCounting,
            'count_estimation_threshold' => $this->countEstimationThreshold,
            'default_cache_ttl' => $this->defaultCacheTtl,
            'max_concurrent_requests' => $this->maxConcurrentRequests,
            'enable_circuit_breaker' => $this->enableCircuitBreaker,
            'api_timeout' => $this->apiTimeout,
            'enable_batching' => $this->enableBatching,
            'batch_size' => $this->batchSize,
            'batch_delay_microseconds' => $this->batchDelayMicroseconds,
            'enable_pagination' => $this->enablePagination,
            'default_page_size' => $this->defaultPageSize,
            'max_page_size' => $this->maxPageSize,
            'enable_lazy_loading' => $this->enableLazyLoading,
            'lazy_loading_chunk_size' => $this->lazyLoadingChunkSize,
            'enable_query_optimization' => $this->enableQueryOptimization,
            'query_optimization_cache_size' => $this->queryOptimizationCacheSize,
        ];
    }
}
