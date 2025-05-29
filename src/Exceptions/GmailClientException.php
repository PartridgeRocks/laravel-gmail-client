<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use Exception;
use PartridgeRocks\GmailClient\Data\Errors\ErrorDTO;

class GmailClientException extends Exception
{
    protected ?ErrorDTO $error = null;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, ?ErrorDTO $error = null)
    {
        parent::__construct($message, $code, $previous);
        $this->error = $error;
    }

    /**
     * Get the error DTO associated with this exception
     */
    public function getError(): ?ErrorDTO
    {
        return $this->error;
    }

    /**
     * Set the error DTO
     */
    public function setError(ErrorDTO $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Check if the exception has an error DTO
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Create an exception from a response array
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response, ?string $message = null): self
    {
        $error = ErrorDTO::fromResponse($response);

        return new self(
            $message ?? $error->message,
            0,
            null,
            $error
        );
    }
}
