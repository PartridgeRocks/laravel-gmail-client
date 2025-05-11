<?php

namespace PartridgeRocks\GmailClient\Gmail\Resources;

use Saloon\Enums\Method;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;

class LabelResource extends BaseResource
{
    /**
     * Retrieves all Gmail labels for the authenticated user.
     *
     * @return Response The HTTP response containing the list of labels.
     */
    public function list(): Response
    {
        return $this->connector->send(new class extends Request
        {
            /**
             * Returns the API endpoint for Gmail label operations.
             *
             * @return string The endpoint path for label-related requests.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/labels';
            }

            /**
             * Specifies that the HTTP method for the request is GET.
             *
             * @return Method The HTTP GET method.
             */
            public function method(): Method
            {
                return Method::GET;
            }
        });
    }

    /**
     * Retrieves a Gmail label by its unique identifier.
     *
     * @param string $id The ID of the label to retrieve.
     * @return Response The HTTP response containing the label details.
     */
    public function get(string $id): Response
    {
        return $this->connector->send(new class($id) extends Request
        {
            /**
 * Initializes the class with the specified label ID.
 *
 * @param string $id The unique identifier for the label.
 */
public function __construct(protected string $id) {}

            /**
             * Returns the API endpoint for accessing a specific Gmail label by its ID.
             *
             * @return string The endpoint URL for the label resource.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/labels/'.$this->id;
            }

            /**
             * Specifies that the HTTP method for this request is GET.
             *
             * @return Method The HTTP GET method.
             */
            public function method(): Method
            {
                return Method::GET;
            }
        });
    }

    /****
     * Creates a new Gmail label with the specified data.
     *
     * @param array $data Associative array containing label properties.
     * @return Response HTTP response from the Gmail API.
     */
    public function create(array $data): Response
    {
        return $this->connector->send(new class($data) extends Request
        {
            /**
 * Initializes the class with the provided data array.
 *
 * @param array $data Data to be used by the instance.
 */
public function __construct(protected array $data) {}

            /**
             * Returns the API endpoint for Gmail label resources.
             *
             * @return string The endpoint path for label operations.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/labels';
            }

            /****
             * Specifies that the HTTP method for the request is POST.
             *
             * @return Method The HTTP method to be used.
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

    /**
     * Updates an existing Gmail label with the specified data.
     *
     * @param string $id The unique identifier of the label to update.
     * @param array $data The data to update the label with.
     * @return Response The HTTP response from the Gmail API.
     */
    public function update(string $id, array $data): Response
    {
        return $this->connector->send(new class($id, $data) extends Request
        {
            /****
 * Initializes a new instance with the specified label ID and data.
 *
 * @param string $id The unique identifier of the label.
 * @param array $data The data associated with the label.
 */
public function __construct(protected string $id, protected array $data) {}

            /**
             * Returns the API endpoint for accessing a specific Gmail label by its ID.
             *
             * @return string The endpoint URL for the label resource.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/labels/'.$this->id;
            }

            /**
             * Specifies that the HTTP method for this request is PUT.
             *
             * @return Method The HTTP PUT method.
             */
            public function method(): Method
            {
                return Method::PUT;
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

    /**
     * Deletes a Gmail label by its ID.
     *
     * @param string $id The unique identifier of the label to delete.
     * @return Response The HTTP response from the Gmail API.
     */
    public function delete(string $id): Response
    {
        return $this->connector->send(new class($id) extends Request
        {
            /****
 * Initializes the class with the specified label ID.
 *
 * @param string $id The unique identifier for the label.
 */
public function __construct(protected string $id) {}

            /**
             * Returns the API endpoint for accessing a specific Gmail label by its ID.
             *
             * @return string The endpoint URL for the label resource.
             */
            public function resolveEndpoint(): string
            {
                return '/users/me/labels/'.$this->id;
            }

            /**
             * Specifies that the HTTP method for the request is DELETE.
             *
             * @return Method The HTTP DELETE method.
             */
            public function method(): Method
            {
                return Method::DELETE;
            }
        });
    }
}
