<?php

namespace PartridgeRocks\GmailClient;

use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\GmailOAuthAuthenticator;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use Illuminate\Support\Collection;

class GmailClient
{
    protected GmailConnector $connector;

    /**
     * Create a new GmailClient instance.
     *
     * @param string|null $accessToken
     */
    public function __construct(?string $accessToken = null)
    {
        $this->connector = new GmailConnector();

        if ($accessToken) {
            $this->authenticate($accessToken);
        }
    }

    /**
     * Authenticate with a token.
     *
     * @param string $accessToken
     * @param string|null $refreshToken
     * @param \DateTimeInterface|null $expiresAt
     * @return $this
     */
    public function authenticate(
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeInterface $expiresAt = null
    ): self {
        $authenticator = new GmailOAuthAuthenticator($accessToken, $refreshToken, 'Bearer', $expiresAt);
        $this->connector->authenticate($authenticator);

        return $this;
    }

    /**
     * Get the authentication resource.
     *
     * @return \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource
     */
    protected function auth(): \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource
    {
        return new \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource($this->connector);
    }

    /**
     * Get the authorization URL for the OAuth flow.
     *
     * @param string $redirectUri
     * @param array $scopes
     * @param array $additionalParams
     * @return string
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
     *
     * @param string $code
     * @param string $redirectUri
     * @return array
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = $this->auth()->exchangeCode($code, $redirectUri);
        $data = $response->json();

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new \DateTime();
            $expiresAt->modify("+{$data['expires_in']} seconds");
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
     *
     * @param string $refreshToken
     * @return array
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->auth()->refreshToken($refreshToken);
        $data = $response->json();

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new \DateTime();
            $expiresAt->modify("+{$data['expires_in']} seconds");
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
     *
     * @return \PartridgeRocks\GmailClient\Gmail\Resources\MessageResource
     */
    protected function messages(): MessageResource
    {
        return new MessageResource($this->connector);
    }

    /**
     * Get the label resource.
     *
     * @return \PartridgeRocks\GmailClient\Gmail\Resources\LabelResource
     */
    protected function labels(): LabelResource
    {
        return new LabelResource($this->connector);
    }

    /**
     * List messages with optional query parameters.
     *
     * @param array $query
     * @return \Illuminate\Support\Collection
     */
    public function listMessages(array $query = []): Collection
    {
        $response = $this->messages()->list($query);
        $data = $response->json();
        
        $messages = collect($data['messages'] ?? []);

        return $messages->map(function ($message) {
            return $this->getMessage($message['id']);
        });
    }

    /**
     * Get a specific message.
     *
     * @param string $id
     * @return \PartridgeRocks\GmailClient\Data\Email
     */
    public function getMessage(string $id): Email
    {
        $response = $this->messages()->get($id, ['format' => 'full']);
        $data = $response->json();
        
        return Email::fromApiResponse($data);
    }

    /**
     * Send a new email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return \PartridgeRocks\GmailClient\Data\Email
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): Email
    {
        $message = $this->createEmailRaw($to, $subject, $body, $options);
        
        $response = $this->messages()->send([
            'raw' => $message,
        ]);
        
        $data = $response->json();
        
        return $this->getMessage($data['id']);
    }

    /**
     * Create a raw email message.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return string
     */
    protected function createEmailRaw(string $to, string $subject, string $body, array $options = []): string
    {
        $from = $options['from'] ?? config('gmail-client.from_email');
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
        $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $email .= chunk_split(base64_encode($body));
        
        return base64_encode($email);
    }

    /**
     * List all labels.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listLabels(): Collection
    {
        $response = $this->labels()->list();
        $data = $response->json();
        
        return collect($data['labels'] ?? [])->map(function ($label) {
            return Label::fromApiResponse($label);
        });
    }

    /**
     * Get a specific label.
     *
     * @param string $id
     * @return \PartridgeRocks\GmailClient\Data\Label
     */
    public function getLabel(string $id): Label
    {
        $response = $this->labels()->get($id);
        $data = $response->json();
        
        return Label::fromApiResponse($data);
    }

    /**
     * Create a new label.
     *
     * @param string $name
     * @param array $options
     * @return \PartridgeRocks\GmailClient\Data\Label
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