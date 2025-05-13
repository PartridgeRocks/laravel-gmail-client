<?php

namespace PartridgeRocks\GmailClient\Gmail\Pagination;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\GmailClient;

class GmailLazyCollection extends LazyCollection
{
    /**
     * Create a new lazy collection of messages.
     *
     * This method creates a memory-efficient collection that loads messages
     * from the Gmail API only when they are accessed. It handles pagination
     * automatically and can fetch either full message details or just IDs.
     *
     * @param  \PartridgeRocks\GmailClient\GmailClient  $client  The Gmail client instance
     * @param  array  $query  Query parameters for filtering messages
     * @param  int  $pageSize  Maximum number of results per page
     * @param  bool  $fullDetails  Whether to fetch full message details
     * @return static
     */
    public static function messages(
        GmailClient $client,
        array $query = [],
        int $pageSize = 100,
        bool $fullDetails = true
    ): static {
        // Set page size in query
        $query['maxResults'] = $pageSize;

        return new static(function () use ($client, $query, $fullDetails) {
            $hasMorePages = true;
            $pageToken = null;

            while ($hasMorePages) {
                // Add page token to query if we have one
                $currentQuery = $pageToken ? array_merge($query, ['pageToken' => $pageToken]) : $query;

                // Get list of message IDs for this page
                // Using the messages().list() method directly since getMessageIds doesn't exist
                $response = $client->messages()->list($currentQuery);
                $data = $response->json();
                
                // Get next page token and check if more pages exist
                $pageToken = $data['nextPageToken'] ?? null;
                $hasMorePages = $pageToken !== null;
                
                // Skip if no messages in this page
                if (empty($data['messages'])) {
                    continue;
                }
                
                // Yield each message (either full details or just ID)
                foreach ($data['messages'] as $message) {
                    if ($fullDetails) {
                        // Fetch full message details when needed
                        yield $client->getMessage($message['id']);
                    } else {
                        // Just yield the basic message data
                        yield [
                            'id' => $message['id'],
                            'threadId' => $message['threadId'] ?? null,
                        ];
                    }
                }
            }
        });
    }

    /**
     * Create a new lazy collection of labels.
     *
     * This method creates a memory-efficient collection that loads labels
     * from the Gmail API only when they are accessed.
     *
     * @param  \PartridgeRocks\GmailClient\GmailClient  $client  The Gmail client instance
     * @return static
     */
    public static function labels(GmailClient $client): static
    {
        return new static(function () use ($client) {
            $response = $client->labels()->list();
            $data = $response->json();
            
            if (empty($data['labels'])) {
                return;
            }
            
            foreach ($data['labels'] as $label) {
                yield $client->getLabel($label['id']);
            }
        });
    }

    /**
     * Convert the lazy collection to a standard collection.
     *
     * This method materializes the lazy collection into a regular collection,
     * which may consume more memory but allows for more operations.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return collect($this->all());
    }
}