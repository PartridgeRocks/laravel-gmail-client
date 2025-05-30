<?php

namespace PartridgeRocks\GmailClient\Gmail;

use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use Saloon\Http\Response;

/**
 * Standardized exception handling patterns for Gmail API operations.
 */
trait ExceptionHandling
{
    /**
     * Handle standard Gmail API response errors.
     *
     * @param  Response  $response  The API response to check
     * @param  string  $resourceType  Type of resource (e.g., 'message', 'label')
     * @param  string  $resourceId  ID of the resource for context
     *
     * @throws NotFoundException When resource is not found (404)
     * @throws AuthenticationException When authentication fails (401)
     * @throws RateLimitException When rate limit is exceeded (429)
     * @throws ValidationException When request data is invalid (400)
     */
    protected function handleApiResponse(Response $response, string $resourceType, string $resourceId = ''): void
    {
        match ($response->status()) {
            config('gmail-client.http_status.client_error.not_found', 404) => throw NotFoundException::{$resourceType}($resourceId),
            config('gmail-client.http_status.client_error.unauthorized', 401) => throw AuthenticationException::invalidToken(),
            config('gmail-client.http_status.client_error.too_many_requests', 429) => throw RateLimitException::quotaExceeded(
                $this->parseRetryAfterHeader($this->getHeaderAsString($response, 'Retry-After'))
            ),
            config('gmail-client.http_status.client_error.bad_request', 400) => throw new ValidationException('Invalid request data provided'),
            default => null
        };
    }

    /**
     * Execute a callable safely with standardized error handling and logging.
     *
     * @param  callable  $callback  The operation to execute
     * @param  mixed  $fallback  The fallback value to return on error
     * @param  string  $operation  Description of the operation for logging
     * @param  array<string, mixed>  $context  Additional context for logging
     * @return mixed Result of callback or fallback value
     */
    protected function safeCall(callable $callback, mixed $fallback, string $operation, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (NotFoundException $e) {
            // Don't log expected not-found errors as warnings
            return $fallback;
        } catch (\Exception $e) {
            $this->logOperationFailure($operation, $e, $context);

            return $fallback;
        }
    }

    /**
     * Log operation failures with standardized format and context.
     *
     * @param  string  $operation  Description of the failed operation
     * @param  \Throwable  $exception  The exception that occurred
     * @param  array<string, mixed>  $context  Additional context for debugging
     */
    protected function logOperationFailure(string $operation, \Throwable $exception, array $context = []): void
    {
        logger()->warning("Gmail operation failed: {$operation}", [
            'operation' => $operation,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'trace_summary' => $this->getTraceSummary($exception),
            ...$context,
        ]);
    }

    /**
     * Log retryable errors with appropriate context.
     *
     * @param  \Throwable  $exception  The retryable exception
     * @param  string  $operation  Description of the operation
     * @param  array<string, mixed>  $context  Additional context
     */
    protected function logRetryableError(\Throwable $exception, string $operation, array $context = []): void
    {
        logger()->info("Gmail operation rate limited: {$operation}", [
            'operation' => $operation,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'retry_after' => $exception instanceof RateLimitException ? $exception->getRetryAfter() : null,
            ...$context,
        ]);
    }

    /**
     * Log authentication errors with security context.
     *
     * @param  \Throwable  $exception  The authentication exception
     * @param  string  $operation  Description of the operation
     * @param  array<string, mixed>  $context  Additional context (avoid sensitive data)
     */
    protected function logAuthenticationError(\Throwable $exception, string $operation, array $context = []): void
    {
        logger()->warning("Gmail authentication failed: {$operation}", [
            'operation' => $operation,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'user_agent' => optional(request())->userAgent(),
            'ip_address' => optional(request())->ip(),
            ...$context,
        ]);
    }

    /**
     * Get a concise summary of the exception trace for logging.
     *
     * @param  \Throwable  $exception  The exception to summarize
     * @return string Concise trace summary
     */
    private function getTraceSummary(\Throwable $exception): string
    {
        $trace = $exception->getTrace();
        $summary = [];

        // Get first 3 relevant trace entries
        foreach (array_slice($trace, 0, 3) as $entry) {
            if (isset($entry['file'], $entry['line'])) {
                $file = basename($entry['file']);
                $summary[] = "{$file}:{$entry['line']}";
            }
        }

        return implode(' → ', $summary);
    }

    /**
     * Get header value as string, handling array/string types safely.
     *
     * @param  Response  $response  The response to get header from
     * @param  string  $headerName  The header name
     * @return string Header value as string
     */
    private function getHeaderAsString(Response $response, string $headerName): string
    {
        $headerValue = $response->header($headerName);

        if (is_array($headerValue)) {
            return $headerValue[0] ?? '0';
        }

        return (string) ($headerValue ?? '0');
    }

    /**
     * Parse the Retry-After header value.
     *
     * @param  string  $value  The Retry-After header value
     * @return int Number of seconds to wait before retrying
     */
    abstract protected function parseRetryAfterHeader(string $value): int;
}
