<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Http\BaseResource;
use Saloon\Http\Response;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class LabelResource extends BaseResource
{
    /**
     * List all labels.
     *
     * @return \Saloon\Http\Response
     */
    public function list(): Response
    {
        return $this->connector->send(new class extends Request {
            public function resolveEndpoint(): string
            {
                return '/users/me/labels';
            }

            public function method(): Method
            {
                return Method::GET;
            }
        });
    }

    /**
     * Get a specific label.
     *
     * @param string $id
     * @return \Saloon\Http\Response
     */
    public function get(string $id): Response
    {
        return $this->connector->send(new class($id) extends Request {
            public function __construct(protected string $id)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/me/labels/' . $this->id;
            }

            public function method(): Method
            {
                return Method::GET;
            }
        });
    }

    /**
     * Create a new label.
     *
     * @param array $data
     * @return \Saloon\Http\Response
     */
    public function create(array $data): Response
    {
        return $this->connector->send(new class($data) extends Request {
            public function __construct(protected array $data)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/me/labels';
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

    /**
     * Update a label.
     *
     * @param string $id
     * @param array $data
     * @return \Saloon\Http\Response
     */
    public function update(string $id, array $data): Response
    {
        return $this->connector->send(new class($id, $data) extends Request {
            public function __construct(protected string $id, protected array $data)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/me/labels/' . $this->id;
            }

            public function method(): Method
            {
                return Method::PUT;
            }

            public function defaultBody(): array
            {
                return $this->data;
            }
        });
    }

    /**
     * Delete a label.
     *
     * @param string $id
     * @return \Saloon\Http\Response
     */
    public function delete(string $id): Response
    {
        return $this->connector->send(new class($id) extends Request {
            public function __construct(protected string $id)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/me/labels/' . $this->id;
            }

            public function method(): Method
            {
                return Method::DELETE;
            }
        });
    }
}