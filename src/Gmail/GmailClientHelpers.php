<?php

namespace PartridgeRocks\GmailClient\Gmail;

use DateTimeImmutable;

trait GmailClientHelpers
{
    /**
     * Parse the Retry-After header which can be in seconds or HTTP date format.
     *
     * @param string $retryAfter The Retry-After header value
     * @return int Number of seconds to wait
     */
    protected function parseRetryAfterHeader(string $retryAfter): int
    {
        // If it's numeric, it's already in seconds
        if (is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }
        
        // Otherwise, it's an HTTP date format
        $date = DateTimeImmutable::createFromFormat('D, d M Y H:i:s \\G\\M\\T', $retryAfter);
        if ($date) {
            $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
            return max(0, $date->getTimestamp() - $now->getTimestamp());
        }
        
        // Default fallback
        return 60;
    }
    
    /**
     * Encode a string using base64url encoding (RFC 4648).
     *
     * @param string $data The data to encode
     * @return string Base64url encoded data
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}