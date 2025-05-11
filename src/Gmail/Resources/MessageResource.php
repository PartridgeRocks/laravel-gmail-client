<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Enums\Method;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;

class MessageResource extends BaseResource
{
    /****
     * Retrieves a list of messages from the user's Gmail mailbox.
     *
     * @param array $query Optional query parameters to filter or paginate the results.
     * @return Response The HTTP response containing the list of messages.
     */
    public function list(array $query = []): Response
    {
        return $this->connector->send(new class($query) extends Request
        {
            /**
             * Initializes the request with the specified query parameters.
             *
             * @param  array  $query  Query parameters to include in the request.
             */
            public function __construct(protected array $query) {}

            /****
             * Returns the API endpoint for accessing the user's messages.
             *
             * @return string The endpoint path for Gmail messages.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/messages';
            }

            /**
             * Returns the HTTP method used for the request.
             *
             * @return Method The HTTP method (GET).
             */
            public function method(): Method
            {
                return Method::GET;
            }

            /**
             * Returns the default query parameters for the resource.
             *
             * @return array The default query parameters.
             */
            public function defaultQuery(): array
            {
                return $this->query;
            }
        });
    }

    /**
     * Retrieves a specific Gmail message by its ID.
     *
     * @param  string  $id  The unique identifier of the message to retrieve.
     * @param  array  $query  Optional query parameters to customize the request.
     * @return Response The HTTP response containing the message data.
     */
    public function get(string $id, array $query = []): Response
    {
        return $this->connector->send(new class($id, $query) extends Request
        {
            /**
             * Initializes the request with a message ID and optional query parameters.
             *
             * @param  string  $id  The unique identifier of the Gmail message.
             * @param  array  $query  Optional query parameters for the request.
             */
            public function __construct(protected string $id, protected array $query) {}

            /**
             * Returns the API endpoint for retrieving a specific Gmail message by its ID.
             *
             * @return string The endpoint path including the message ID.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/messages/'.$this->id;
            }

            /**
             * Returns the HTTP method used for the request.
             *
             * @return Method The HTTP method (GET).
             */
            public function method(): Method
            {
                return Method::GET;
            }

            /**
             * Returns the default query parameters for the resource.
             *
             * @return array The default query parameters.
             */
            public function defaultQuery(): array
            {
                return $this->query;
            }
        });
    }

    /**
     * Sends an email message using the Gmail API.
     *
     * @param  array  $data  The message content and metadata to be sent.
     * @return Response The HTTP response from the Gmail API.
     */
    public function send(array $data): Response
    {
        return $this->connector->send(new class($data) extends Request
        {
            /**
             * Initializes the MessageResource with the provided data.
             *
             * @param  array  $data  Data used to configure the resource.
             */
            public function __construct(protected array $data) {}

            /**
             * Returns the API endpoint for sending a Gmail message.
             *
             * @return string The endpoint path for sending messages.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/messages/send';
            }

            /**
             * Returns the HTTP method used for the request.
             *
             * @return Method The HTTP POST method.
             */
            public function method(): Method
            {
                return Method::POST;
            }

            /**
             * Returns the request body data for the HTTP request.
             *
             * @return array The data to be sent as the request body.
             */
            public function defaultBody(): array
            {
                return $this->data;
            }
        });
    }
}
