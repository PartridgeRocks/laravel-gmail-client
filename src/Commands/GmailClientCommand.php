<?php

namespace PartridgeRocks\GmailClient\Commands;

use Illuminate\Console\Command;
use PartridgeRocks\GmailClient\GmailClient;

class GmailClientCommand extends Command
{
    public $signature = 'gmail-client:test {--list-messages : List recent messages} {--list-labels : List available labels} {--authenticate : Get authentication URL}';

    public $description = 'Test the Gmail Client integration';

    protected GmailClient $client;

    public function __construct(GmailClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

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
        if (!session('gmail_access_token') && !config('gmail-client.access_token')) {
            $this->error('No access token found. Please authenticate first using the --authenticate option.');

            return self::FAILURE;
        }

        if ($this->option('list-messages')) {
            $this->info('Fetching recent messages...');

            try {
                $messages = $this->client->listMessages(['maxResults' => 10]);

                $this->table(
                    ['ID', 'From', 'Subject', 'Date'],
                    $messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'from' => $message->from,
                            'subject' => $message->subject,
                            'date' => $message->internalDate->format('Y-m-d H:i'),
                        ];
                    })
                );
            } catch (\Exception $e) {
                $this->error('Error fetching messages: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        if ($this->option('list-labels')) {
            $this->info('Fetching labels...');

            try {
                $labels = $this->client->listLabels();

                $this->table(
                    ['ID', 'Name', 'Type', 'Messages', 'Unread'],
                    $labels->map(function ($label) {
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
                $this->error('Error fetching labels: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        if (!$this->option('list-messages') && !$this->option('list-labels') && !$this->option('authenticate')) {
            $this->info('Gmail Client is configured.');
            $this->line('Use --list-messages to list recent messages');
            $this->line('Use --list-labels to list available labels');
            $this->line('Use --authenticate to get the authentication URL');
        }

        return self::SUCCESS;
    }
}
