<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Http\BaseResource;
use Saloon\Http\Response;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class MessageResource extends BaseResource
{
    /**
     * List messages in the user's mailbox.
     *
     * @param array $query
     * @return \Saloon\Http\Response
     */
    public function list(array $query = []): Response
    {
        return $this->connector->send(new class($query) extends Request {
            public function __construct(protected array $query)
            {
            }

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
     *
     * @param string $id
     * @param array $query
     * @return \Saloon\Http\Response
     */
    public function get(string $id, array $query = []): Response
    {
        return $this->connector->send(new class($id, $query) extends Request {
            public function __construct(protected string $id, protected array $query)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/me/messages/' . $this->id;
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
     *
     * @param array $data
     * @return \Saloon\Http\Response
     */
    public function send(array $data): Response
    {
        return $this->connector->send(new class($data) extends Request {
            public function __construct(protected array $data)
            {
            }

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