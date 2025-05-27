<?php

namespace PartridgeRocks\GmailClient\Data;

use Spatie\LaravelData\Data;

class Contact extends Data
{
    /**
     * Create a new Contact instance.
     *
     * @param  string  $email  The email address
     * @param  string|null  $name  The display name (optional)
     * @param  string|null  $domain  The email domain (auto-extracted)
     */
    public function __construct(
        public string $email,
        public ?string $name = null,
        public ?string $domain = null,
    ) {
        // Auto-extract domain if not provided
        if ($this->domain === null) {
            $this->domain = $this->extractDomain($this->email);
        }
    }

    /**
     * Parse an email string that may contain name and email.
     *
     * Handles formats like:
     * - "John Doe <john@example.com>"
     * - "john@example.com"
     * - "<john@example.com>"
     * - "John Doe" <john@example.com>
     *
     * @param  string  $emailString  The raw email string
     */
    public static function parse(string $emailString): self
    {
        $emailString = trim($emailString);

        // Pattern to match quoted names with possible escaped quotes: "Name with \"quotes\"" <email>
        if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"\s*<([^>]+)>$/', $emailString, $matches)) {
            $name = str_replace('\\"', '"', $matches[1]); // Unescape quotes
            $email = trim($matches[2]);

            return new self(
                email: $email,
                name: $name !== '' ? $name : null
            );
        }

        // Pattern to match unquoted names: Name <email@domain.com>
        if (preg_match('/^([^<>"]+?)\s*<([^>]+)>$/', $emailString, $matches)) {
            $name = trim($matches[1]);
            $email = trim($matches[2]);

            return new self(
                email: $email,
                name: $name !== '' ? $name : null
            );
        }

        // Pattern to match: <email@domain.com>
        if (preg_match('/^<([^>]+)>$/', $emailString, $matches)) {
            return new self(
                email: trim($matches[1])
            );
        }

        // Check if it's just an email address
        if (filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            return new self(
                email: $emailString
            );
        }

        // If no valid email found, treat the whole string as email
        // This preserves the original behavior for edge cases
        return new self(
            email: $emailString
        );
    }

    /**
     * Parse multiple email addresses from a string.
     *
     * @param  string  $emailsString  Comma-separated email addresses
     * @return array<self>
     */
    public static function parseMultiple(string $emailsString): array
    {
        if (empty(trim($emailsString))) {
            return [];
        }

        // Split by comma, but be careful about commas within quoted names
        $emails = [];
        $parts = [];
        $inQuotes = false;
        $inBrackets = false;
        $current = '';

        for ($i = 0; $i < strlen($emailsString); $i++) {
            $char = $emailsString[$i];

            if ($char === '"' && ! $inBrackets) {
                $inQuotes = ! $inQuotes;
            } elseif ($char === '<' && ! $inQuotes) {
                $inBrackets = true;
            } elseif ($char === '>' && ! $inQuotes) {
                $inBrackets = false;
            } elseif ($char === ',' && ! $inQuotes && ! $inBrackets) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $char;
        }

        // Add the last part
        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        // Parse each part
        foreach ($parts as $part) {
            $emails[] = self::parse($part);
        }

        return $emails;
    }

    /**
     * Extract the domain from an email address.
     *
     * @param  string  $email  The email address
     * @return string|null The domain or null if invalid
     */
    private function extractDomain(string $email): ?string
    {
        $atPos = strrpos($email, '@');

        if ($atPos === false) {
            return null;
        }

        return substr($email, $atPos + 1);
    }

    /**
     * Get the display name or email if name is not available.
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get the local part of the email (before @).
     */
    public function getLocalPart(): ?string
    {
        $atPos = strrpos($this->email, '@');

        if ($atPos === false) {
            return null;
        }

        return substr($this->email, 0, $atPos);
    }

    /**
     * Check if this contact belongs to a specific domain.
     *
     * @param  string  $domain  The domain to check
     */
    public function isFromDomain(string $domain): bool
    {
        return strcasecmp($this->domain ?? '', $domain) === 0;
    }

    /**
     * Format the contact as a string suitable for email headers.
     */
    public function format(): string
    {
        if ($this->name) {
            // Check if name contains special characters that need quoting
            if (preg_match('/[()<>@,;:\\".[\]]/', $this->name)) {
                return "\"{$this->name}\" <{$this->email}>";
            }

            return "{$this->name} <{$this->email}>";
        }

        return $this->email;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'domain' => $this->domain,
            'display_name' => $this->getDisplayName(),
            'local_part' => $this->getLocalPart(),
        ];
    }

    /**
     * Get a string representation.
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
