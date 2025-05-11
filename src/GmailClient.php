<?php

namespace PartridgeRocks\GmailClient;

use Illuminate\Support\Collection;
use PartridgeRocks\GmailClient\Data\Email;
use PartridgeRocks\GmailClient\Data\Label;
use PartridgeRocks\GmailClient\Gmail\GmailConnector;
use PartridgeRocks\GmailClient\Gmail\GmailOAuthAuthenticator;
use PartridgeRocks\GmailClient\Gmail\Resources\LabelResource;
use PartridgeRocks\GmailClient\Gmail\Resources\MessageResource;

class GmailClient
{
    protected GmailConnector $connector;

    /**
     * Initializes a new GmailClient instance, optionally authenticating with the provided access token.
     *
     * @param string|null $accessToken Optional access token for immediate authentication.
     */
    public function __construct(?string $accessToken = null)
    {
        $this->connector = new GmailConnector;

        if ($accessToken) {
            $this->authenticate($accessToken);
        }
    }

    /**
     * Sets the OAuth authentication credentials for the Gmail client.
     *
     * Updates the client to use the provided access token, and optionally a refresh token and expiration time, for subsequent API requests.
     *
     * @param string $accessToken The OAuth access token.
     * @param string|null $refreshToken Optional refresh token for renewing access.
     * @param \DateTimeInterface|null $expiresAt Optional expiration time of the access token.
     * @return $this The current GmailClient instance for method chaining.
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

    /****
     * Returns an AuthResource instance for performing OAuth-related operations using the current connector.
     *
     * @return \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource Auth resource handler.
     */
    protected function auth(): \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource
    {
        return new \PartridgeRocks\GmailClient\Gmail\Resources\AuthResource($this->connector);
    }

    /****
     * Generates the OAuth authorization URL for user consent.
     *
     * @param string $redirectUri The URI to redirect to after authorization.
     * @param array $scopes Optional list of OAuth scopes to request.
     * @param array $additionalParams Optional additional query parameters for the authorization request.
     * @return string The complete authorization URL for initiating the OAuth flow.
     */
    public function getAuthorizationUrl(
        string $redirectUri,
        array $scopes = [],
        array $additionalParams = []
    ): string {
        return $this->auth()->getAuthorizationUrl($redirectUri, $scopes, $additionalParams);
    }

    /**
     * Exchanges an OAuth authorization code for access and refresh tokens, updates authentication, and returns the token data.
     *
     * @param string $code The authorization code received from the OAuth flow.
     * @param string $redirectUri The redirect URI used in the OAuth flow.
     * @return array The token data including access token, refresh token, and expiration information.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = $this->auth()->exchangeCode($code, $redirectUri);
        $data = $response->json();

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new \DateTime;
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
     * Obtains a new access token using the provided refresh token and updates the client's authentication state.
     *
     * @param string $refreshToken The refresh token to use for obtaining a new access token.
     * @return array The token response data, including the new access token and related information.
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->auth()->refreshToken($refreshToken);
        $data = $response->json();

        // Set the current token
        $expiresAt = null;
        if (isset($data['expires_in'])) {
            $expiresAt = new \DateTime;
            $expiresAt->modify("+{$data['expires_in']} seconds");
        }

        $this->authenticate(
            $data['access_token'],
            $data['refresh_token'] ?? $refreshToken,
            $expiresAt
        );

        return $data;
    }

    /****
     * Returns a new MessageResource instance for performing message-related Gmail API operations.
     *
     * @return MessageResource The resource handler for message operations.
     */
    protected function messages(): MessageResource
    {
        return new MessageResource($this->connector);
    }

    /****
     * Returns a new LabelResource instance for performing label-related API operations.
     *
     * @return LabelResource The label resource handler.
     */
    protected function labels(): LabelResource
    {
        return new LabelResource($this->connector);
    }

    /**
     * Retrieves a collection of email messages matching the specified query parameters.
     *
     * For each message found, fetches the full `Email` object by its ID. Returns a collection of `Email` objects.
     *
     * @param array $query Optional query parameters for filtering messages.
     * @return Collection Collection of `Email` objects.
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
     * Retrieves a specific email message by its ID.
     *
     * @param string $id The unique identifier of the email message.
     * @return Email The email message as an Email object.
     */
    public function getMessage(string $id): Email
    {
        $response = $this->messages()->get($id, ['format' => 'full']);
        $data = $response->json();

        return Email::fromApiResponse($data);
    }

    /**
     * Sends an email message to the specified recipient.
     *
     * Constructs and sends a MIME email with the given recipient, subject, and body. Optional headers such as CC and BCC can be provided via the $options array. Returns the sent email as an Email object.
     *
     * @param string $to Recipient email address.
     * @param string $subject Email subject line.
     * @param string $body Email message body.
     * @param array $options Optional headers (e.g., 'cc', 'bcc').
     * @return Email The sent email as an Email object.
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
     * Constructs a base64-encoded raw email message with optional CC and BCC headers.
     *
     * Builds a MIME email string with the specified recipient, subject, and HTML body, including optional 'from', 'cc', and 'bcc' headers from the options array. The message body is base64-encoded and the entire email is returned as a base64-encoded string suitable for Gmail API transmission.
     *
     * @param string $to Recipient email address.
     * @param string $subject Email subject line.
     * @param string $body HTML content of the email.
     * @param array $options Optional headers: 'from', 'cc', and 'bcc'.
     * @return string Base64-encoded raw email message.
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
     * Retrieves all labels from the Gmail account.
     *
     * @return Collection Collection of Label objects representing each label.
     */
    public function listLabels(): Collection
    {
        $response = $this->labels()->list();
        $data = $response->json();

        return collect($data['labels'] ?? [])->map(function ($label) {
            return Label::fromApiResponse($label);
        });
    }

    /****
     * Retrieves a label by its ID and returns it as a Label object.
     *
     * @param string $id The unique identifier of the label to retrieve.
     * @return Label The label corresponding to the provided ID.
     */
    public function getLabel(string $id): Label
    {
        $response = $this->labels()->get($id);
        $data = $response->json();

        return Label::fromApiResponse($data);
    }

    /**
     * Creates a new Gmail label with the specified name and options.
     *
     * @param string $name The name of the label to create.
     * @param array $options Optional additional label properties.
     * @return Label The created label as a Label object.
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
