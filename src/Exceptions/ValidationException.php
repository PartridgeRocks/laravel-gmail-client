<?php

namespace PartridgeRocks\GmailClient\Exceptions;

use PartridgeRocks\GmailClient\Data\Errors\ValidationErrorDTO;

class ValidationException extends GmailClientException
{
    public static function missingRequiredField(string $field): self
    {
        $error = ValidationErrorDTO::missingRequiredField($field);

        return new static("Missing required field: '{$field}'.", 422, null, $error);
    }

    public static function invalidEmailAddress(string $email): self
    {
        $error = ValidationErrorDTO::forField('email', "Invalid email address: '{$email}'.");

        return new static("Invalid email address: '{$email}'.", 422, null, $error);
    }

    public static function invalidMessageFormat(): self
    {
        $error = ValidationErrorDTO::forField('message', 'The email message format is invalid.');

        return new static('The email message format is invalid.', 422, null, $error);
    }

    /**
     * Create a validation exception with multiple errors
     *
     * @param array<string, mixed> $errors
     */
    public static function withErrors(array $errors): self
    {
        $error = ValidationErrorDTO::withErrors($errors);

        return new static('Multiple validation errors occurred.', 422, null, $error);
    }

    /**
     * Create from an API validation response
     *
     * @param array<string, mixed> $response
     */
    public static function fromValidationResponse(array $response): self
    {
        $errorData = $response['error'] ?? $response;
        $errors = [];

        // Extract validation errors from Google API response if available
        if (isset($errorData['fieldViolations'])) {
            foreach ($errorData['fieldViolations'] as $violation) {
                $errors[$violation['field']] = $violation['description'] ?? 'Invalid value';
            }
        }

        if (empty($errors)) {
            $error = ValidationErrorDTO::forField(
                'request',
                $errorData['message'] ?? 'Validation error',
                $response
            );

            return new static($error->message, 422, null, $error);
        }

        $error = ValidationErrorDTO::withErrors($errors, $response);

        return new static('Multiple validation errors occurred.', 422, null, $error);
    }

    /**
     * Create an exception from a response array
     *
     * @param array<string, mixed> $response The response data
     * @param string|null $message Optional custom message
     */
    public static function fromResponse(array $response, ?string $message = null): self
    {
        $errorData = $response['error'] ?? $response;
        $defaultMessage = $errorData['message'] ?? 'Validation error';

        $error = ValidationErrorDTO::forField(
            'request',
            $message ?? $defaultMessage,
            $response
        );

        return new static($message ?? $defaultMessage, 422, null, $error);
    }
}
