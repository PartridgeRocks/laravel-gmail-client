<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Enums\Method;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;

class MessageResource extends BaseResource
{
    /**
     * List messages in the user's mailbox.
     */
    public function list(array $query = []): Response
    {
        return $this->connector->send(new class($query) extends Request
        {
            public function __construct(protected array $query) {}

            public function resolveEndpoint(): string
            {
                return '/users/me/messages';
            }

            public function method(): Method
            {
                return Method::GET;
            }

            public function defaultQuery(): array
            {
                return $this->query;
            }
        });
    }

    /**
     * Get a specific message.
     */
    public function get(string $id, array $query = []): Response
    {
        return $this->connector->send(new class($id, $query) extends Request
        {
            public function __construct(protected string $id, protected array $query) {}

            public function resolveEndpoint(): string
            {
                return '/users/me/messages/'.$this->id;
            }

            public function method(): Method
            {
                return Method::GET;
            }

            public function defaultQuery(): array
            {
                return $this->query;
            }
        });
    }

    /**
     * Send a message.
     */
    public function send(array $data): Response
    {
        return $this->connector->send(new class($data) extends Request
        {
            public function __construct(protected array $data) {}

            public function resolveEndpoint(): string
            {
                return '/users/me/messages/send';
            }

            public function method(): Method
            {
                return Method::POST;
            }

            public function defaultBody(): array
            {
                return $this->data;
            }
        });
    }
}
