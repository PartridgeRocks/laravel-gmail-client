<?php

namespace PartridgeRocks\GmailClient\Exceptions;

class NotFoundException extends GmailClientException
{
    public static function message(string $id): self
    {
        return new static("Email message with ID '{$id}' not found.");
    }

    public static function label(string $id): self
    {
        return new static("Label with ID '{$id}' not found.");
    }

    public static function thread(string $id): self
    {
        return new static("Thread with ID '{$id}' not found.");
    }
}