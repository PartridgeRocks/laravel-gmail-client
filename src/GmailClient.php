<?php

namespace PartridgeRocks\GmailClient;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\GmailClientException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\Gmail\GmailClientHelpers;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\GmailOAuthAuthenticator;
use PartridgeRocks\GmailClient\Gmail\Pagination\GmailPaginator;
use Illuminate\Support\LazyCollection;
use PartridgeRocks\GmailClient\Gmail\Requests\Labels\ListLabelsRequest;
use PartridgeRocks\GmailClient\Gmail\Requests\Messages\ListMessagesRequest;
use PartridgeRocks\GmailClient\Gmail\Resources\AuthResource;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class GmailClient
{
    use GmailClientHelpers;

    protected GmailConnector $connector;

    /**
     * Create a new GmailClient instance.
     */
    public function __construct(?string $accessToken = null)
    {
        $this->connector = new GmailConnector;

        if ($accessToken) {
            $this->authenticate($accessToken);
        }
    }

    /**
     * Get the connector instance.
     */
    public function getConnector(): GmailConnector
    {
        return $this->connector;
    }

    /**
     * Authenticate with a token.
     *
     * @return $this
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeInterface $expiresAt = null
    ): self {
        if (empty($accessToken)) {
            throw AuthenticationException::missingToken();
        }

        $authenticator = new GmailOAuthAuthenticator($accessToken, $refreshToken, 'Bearer', $expiresAt);
        $this->connector->authenticate($authenticator);

        return $this;
    }

    /**
     * Get the authentication resource.
     */
    protected function auth(): AuthResource
    {
        return new AuthResource($this->connector);
    }

    /**
     * Get the authorization URL for the OAuth flow.
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        return $this->auth()->getAuthorizationUrl($redirectUri, $scopes, $additionalParams);
    }

    /**
     * Exchange an authorization code for an access token.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = $this->auth()->exchangeCode($code, $redirectUri);
        $data = $response->json();

        // Check for error in response
        if (isset($data['error'])) {
            $errorMessage = $data['error_description'] ?? $data['error'];

            throw new AuthenticationException("OAuth error: {$errorMessage}");
        }

        // Verify required keys exist
        if (! isset($data['access_token'])) {
            throw new AuthenticationException('Invalid OAuth response: missing access_token');
        }

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new DateTimeImmutable;
            $expiresAt = $expiresAt->modify("+{$data['expires_in']} seconds");
        }

        $this->authenticate(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            $expiresAt
        );

        return $data;
    }

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->auth()->refreshToken($refreshToken);
        $data = $response->json();

        // Check for error in response
        if (isset($data['error'])) {
            $errorMessage = $data['error_description'] ?? $data['error'];

            throw new AuthenticationException("OAuth error: {$errorMessage}");
        }

        // Verify required keys exist
        if (! isset($data['access_token'])) {
            throw new AuthenticationException('Invalid OAuth response: missing access_token');
        }

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new DateTimeImmutable;
            $expiresAt = $expiresAt->modify("+{$data['expires_in']} seconds");
        }

        $this->authenticate(
            $data['access_token'],
            $data['refresh_token'] ?? $refreshToken,
            $expiresAt
        );

        return $data;
    }

    /**
     * Get the message resource.
     */
    protected function messages(): MessageResource
    {
        return new MessageResource($this->connector);
    }

    /**
     * Get the label resource.
     */
    protected function labels(): LabelResource
    {
        return new LabelResource($this->connector);
    }

    /**
     * List messages with optional query parameters.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  bool  $fullDetails  Whether to fetch full message details (only applies with lazy=true)
     * @return Collection|GmailPaginator|\Illuminate\Support\LazyCollection
     */
    public function listMessages(
        array $query = [],
        bool $paginate = false,
        int $maxResults = 100,
        bool $lazy = false,
        bool $fullDetails = true
    ): mixed {
        if ($lazy) {
            return $this->lazyLoadMessages($query, $maxResults, $fullDetails);
        }

        if ($paginate) {
            return $this->paginateMessages($query, $maxResults);
        }

        $response = $this->messages()->list($query);
        $data = $response->json();

        $messages = collect($data['messages'] ?? []);

        return $messages->map(function ($message) {
            return $this->getMessage($message['id']);
        });
    }

    /**
     * Create a paginator for messages.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  int  $maxResults  Maximum number of results per page
     */
    public function paginateMessages(array $query = [], int $maxResults = 100): GmailPaginator
    {
        $paginator = new GmailPaginator(
            $this->connector,
            ListMessagesRequest::class,
            'messages',
            $maxResults
        );

        return $paginator;
    }

    /**
     * Create a lazy-loading collection for messages.
     * This provides memory-efficient iteration over messages.
     *
     * @param  array  $query  Query parameters for filtering messages
     * @param  int  $maxResults  Maximum number of results per page
     * @param  bool  $fullDetails  Whether to fetch full message details or just basic info
     * @return \PartridgeRocks\GmailClient\Gmail\Pagination\GmailLazyCollection
     */
    public function lazyLoadMessages(array $query = [], int $maxResults = 100, bool $fullDetails = true): Gmail\Pagination\GmailLazyCollection
    {
        return Gmail\Pagination\GmailLazyCollection::messages($this, $query, $maxResults, $fullDetails);
    }

    /**
     * Create a lazy-loading collection for labels.
     * This provides memory-efficient iteration over labels.
     *
     * @return \PartridgeRocks\GmailClient\Gmail\Pagination\GmailLazyCollection
     */
    public function lazyLoadLabels(): Gmail\Pagination\GmailLazyCollection
    {
        return Gmail\Pagination\GmailLazyCollection::labels($this);
    }

    /**
     * Get a specific message.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\NotFoundException
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function getMessage(string $id): Email
    {
        try {
            $response = $this->messages()->get($id, ['format' => 'full']);

            if ($response->status() === 404) {
                throw NotFoundException::message($id);
            }

            if ($response->status() === 401) {
                throw AuthenticationException::invalidToken();
            }

            if ($response->status() === 429) {
                $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After', '0'));

                throw RateLimitException::quotaExceeded($retryAfter);
            }

            $data = $response->json();

            return Email::fromApiResponse($data);
        } catch (\Saloon\Exceptions\Request\FatalRequestException $e) {
            $response = $e->getResponse();

            if ($response && $response->status() === 404) {
                throw NotFoundException::message($id);
            }

            if ($response && $response->status() === 401) {
                throw AuthenticationException::invalidToken();
            }

            if ($response && $response->status() === 429) {
                $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After', '0'));

                throw RateLimitException::quotaExceeded($retryAfter);
            }

            throw new GmailClientException(
                "Error retrieving message with ID '{$id}': ".$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Send a new email.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\ValidationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\AuthenticationException
     * @throws \PartridgeRocks\GmailClient\Exceptions\RateLimitException
     * @throws \PartridgeRocks\GmailClient\Exceptions\GmailClientException
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email
    {
        // Validate email address
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmailAddress($to);
        }

        // Validate required fields
        if (empty($subject)) {
            throw ValidationException::missingRequiredField('subject');
        }

        try {
            $message = $this->createEmailRaw($to, $subject, $body, $options);

            $response = $this->messages()->send([
                'raw' => $message,
            ]);

            if ($response->status() === 401) {
                throw AuthenticationException::invalidToken();
            }

            if ($response->status() === 429) {
                $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After', '0'));

                throw RateLimitException::quotaExceeded($retryAfter);
            }

            $data = $response->json();

            return $this->getMessage($data['id']);
        } catch (\Saloon\Exceptions\Request\FatalRequestException $e) {
            $response = $e->getResponse();

            if ($response && $response->status() === 401) {
                throw AuthenticationException::invalidToken();
            }

            if ($response && $response->status() === 429) {
                $retryAfter = $this->parseRetryAfterHeader($response->header('Retry-After', '0'));

                throw RateLimitException::quotaExceeded($retryAfter);
            }

            throw new GmailClientException(
                'Error sending email: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create a raw email message.
     *
     * @throws \PartridgeRocks\GmailClient\Exceptions\ValidationException
     */
    protected function createEmailRaw(string $to, string $subject, string $body, array $options = []): string
    {
        $from = $options['from'] ?? config('gmail-client.from_email');

        if (empty($from)) {
            throw ValidationException::missingRequiredField('from_email');
        }

        // Validate sender email address
        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmailAddress($from);
        }

        $cc = $options['cc'] ?? null;
        $bcc = $options['bcc'] ?? null;

        $email = "From: {$from}\r\n";
        $email .= "To: {$to}\r\n";

        if ($cc) {
            $email .= "Cc: {$cc}\r\n";
        }

        if ($bcc) {
            $email .= "Bcc: {$bcc}\r\n";
        }

        $email .= "Subject: {$subject}\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/html; charset=utf-8\r\n";
        $email .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $email .= quoted_printable_encode($body);

        // Gmail API requires base64url encoding, not standard base64
        return $this->base64UrlEncode($email);
    }

    /**
     * List all labels.
     *
     * @param  bool  $paginate  Whether to return a paginator for all results
     * @param  bool  $lazy  Whether to return a lazy collection for memory-efficient iteration
     * @param  int  $maxResults  Maximum number of results per page
     * @return Collection|GmailPaginator|Gmail\Pagination\GmailLazyCollection
     */
    public function listLabels(bool $paginate = false, bool $lazy = false, int $maxResults = 100): mixed
    {
        if ($paginate) {
            return $this->paginateLabels($maxResults);
        }

        if ($lazy) {
            return $this->lazyLoadLabels();
        }

        $response = $this->labels()->list();
        $data = $response->json();

        return collect($data['labels'] ?? [])->map(function ($label) {
            return Label::fromApiResponse($label);
        });
    }

    /**
     * Create a paginator for labels.
     *
     * @param  int  $maxResults  Maximum number of results per page
     */
    public function paginateLabels(int $maxResults = 100): GmailPaginator
    {
        $paginator = new GmailPaginator(
            $this->connector,
            ListLabelsRequest::class,
            'labels',
            $maxResults
        );

        return $paginator;
    }

    /**
     * Get a specific label.
     */
    public function getLabel(string $id): Label
    {
        $response = $this->labels()->get($id);
        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Create a new label.
     */
    public function createLabel(string $name, array $options = []): Label
    {
        $response = $this->labels()->create([
            'name' => $name,
            ...$options,
        ]);

        $data = $response->json();

        return Label::fromApiResponse($data);
    }
}
