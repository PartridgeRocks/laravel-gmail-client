<?php

namespace PartridgeRocks\GmailClient\Commands;

use Illuminate\Console\Command;
use PartridgeRocks\GmailClient\GmailClient;

class GmailClientCommand extends Command
{
    public $signature = 'gmail-client:test {--list-messages : List recent messages} {--list-labels : List available labels} {--authenticate : Get authentication URL}';

    public $description = 'Test the Gmail Client integration';

    protected GmailClient $client;

    /**
     * Creates a new GmailClientCommand instance with the provided Gmail client.
     *
     * @param  GmailClient  $client  The Gmail client used for API interactions.
     */
    public function __construct(GmailClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    /**
     * Executes the Gmail Client test command based on provided options.
     *
     * Depending on the command-line flags, this method outputs the Gmail API authentication URL, lists recent Gmail messages, or lists available Gmail labels. If no access token is found when required, it prompts the user to authenticate. If no options are specified, it displays usage instructions.
     *
     * @return int Command exit status code.
     */
    public function handle(): int
    {
        if ($this->option('authenticate')) {
            $this->info('Gmail API authentication URL:');
            $url = $this->client->getAuthorizationUrl(
                config('gmail-client.redirect_uri'),
                config('gmail-client.scopes')
            );
            $this->line($url);

            return self::SUCCESS;
        }

        // Check if we have an access token
        if (! session('gmail_access_token') && ! config('gmail-client.access_token')) {
            $this->error('No access token found. Please authenticate first using the --authenticate option.');

            return self::FAILURE;
        }

        if ($this->option('list-messages')) {
            $this->info('Fetching recent messages...');

            try {
                $messages = $this->client->listMessages(['maxResults' => 10]);

                // Convert to collection for consistent interface
                if ($messages instanceof \Illuminate\Support\Collection) {
                    $messagesCollection = $messages;
                } elseif (method_exists($messages, 'toCollection')) {
                    /** @var \Illuminate\Support\Collection<int, \PartridgeRocks\GmailClient\Data\Email> $messagesCollection */
                    $messagesCollection = $messages->toCollection();
                } else {
                    $messagesCollection = collect([]);
                }

                $this->table(
                    ['ID', 'From', 'Subject', 'Date'],
                    $messagesCollection->map(function (\PartridgeRocks\GmailClient\Data\Email $message): array {
                        return [
                            'id' => $message->id,
                            'from' => $message->from ?? 'Unknown',
                            'subject' => $message->subject ?? 'No Subject',
                            'date' => $message->internalDate->format('Y-m-d H:i'),
                        ];
                    })
                );
            } catch (\Exception $e) {
                $this->error('Error fetching messages: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if ($this->option('list-labels')) {
            $this->info('Fetching labels...');

            try {
                $labels = $this->client->listLabels();

                // Convert to collection for consistent interface
                if ($labels instanceof \Illuminate\Support\Collection) {
                    $labelsCollection = $labels;
                } elseif (method_exists($labels, 'toCollection')) {
                    /** @var \Illuminate\Support\Collection<int, \PartridgeRocks\GmailClient\Data\Label> $labelsCollection */
                    $labelsCollection = $labels->toCollection();
                } else {
                    $labelsCollection = collect([]);
                }

                $this->table(
                    ['ID', 'Name', 'Type', 'Messages', 'Unread'],
                    $labelsCollection->map(function (\PartridgeRocks\GmailClient\Data\Label $label) {
                        return [
                            'id' => $label->id,
                            'name' => $label->name,
                            'type' => $label->type ?? 'custom',
                            'messages' => $label->messagesTotal ?? 0,
                            'unread' => $label->messagesUnread ?? 0,
                        ];
                    })
                );
            } catch (\Exception $e) {
                $this->error('Error fetching labels: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if (! $this->option('list-messages') && ! $this->option('list-labels') && ! $this->option('authenticate')) {
            $this->info('Gmail Client is configured.');
            $this->line('Use --list-messages to list recent messages');
            $this->line('Use --list-labels to list available labels');
            $this->line('Use --authenticate to get the authentication URL');
        }

        return self::SUCCESS;
    }
}
