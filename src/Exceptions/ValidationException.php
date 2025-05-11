<?php

namespace PartridgeRocks\GmailClient\Exceptions;

class ValidationException extends GmailClientException
{
    public static function missingRequiredField(string $field): self
    {
        return new static("Missing required field: '{$field}'.");
    }

    public static function invalidEmailAddress(string $email): self
    {
        return new static("Invalid email address: '{$email}'.");
    }

    public static function invalidMessageFormat(): self
    {
        return new static('The email message format is invalid.');
    }
}
