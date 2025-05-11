# Laravel Gmail Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/partridgerocks/gmail-client.svg?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/partridgerocks/gmail-client/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/partridgerocks/gmail-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/partridgerocks/gmail-client/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/partridgerocks/gmail-client/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/partridgerocks/gmail-client.svg?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)

A Laravel package that integrates with the Gmail API to seamlessly manage emails within your application. Built with [Saloon PHP](https://github.com/saloonphp/saloon) and [Laravel Data](https://github.com/spatie/laravel-data).

## Features

- OAuth authentication with Gmail API
- Read emails and threads
- Send emails
- Manage labels
- Access Gmail inbox data with a clean API
- Integrates with Laravel's service container

## Installation

You can install the package via composer:

```bash
composer require partridgerocks/gmail-client
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="gmail-client-config"
```

This is the contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Gmail API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Gmail API client ID and client secret, obtained from the
    | Google Developer Console.
    |
    */
    'client_id' => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),
    'redirect_uri' => env('GMAIL_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Gmail API Scopes
    |--------------------------------------------------------------------------
    |
    | The scopes requested when authenticating with Google.
    | See https://developers.google.com/gmail/api/auth/scopes for available scopes.
    |
    */
    'scopes' => [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.send',
        'https://www.googleapis.com/auth/gmail.compose',
        'https://www.googleapis.com/auth/gmail.modify',
        'https://www.googleapis.com/auth/gmail.labels',
    ],

    // Additional configuration options...
];
```

## Setup

### Google API Setup

1. Go to the [Google Developer Console](https://console.developers.google.com/)
2. Create a new project
3. Enable the Gmail API for your project
4. Configure the OAuth consent screen
5. Create OAuth credentials (Web application type)
6. Add your authorized redirect URI (this should match your `GMAIL_REDIRECT_URI` config value)
7. Copy the Client ID and Client Secret to your `.env` file:

```
GMAIL_CLIENT_ID=your-client-id
GMAIL_CLIENT_SECRET=your-client-secret
GMAIL_REDIRECT_URI=https://your-app.com/gmail/auth/callback
GMAIL_FROM_EMAIL=your-email@gmail.com
```

## Usage

### Authentication

The package provides two ways to authenticate with the Gmail API:

#### 1. Manual authentication flow

```php
use PartridgeRocks\GmailClient\Facades\GmailClient;

// Get the authorization URL
$authUrl = GmailClient::getAuthorizationUrl(
    config('gmail-client.redirect_uri'),
    config('gmail-client.scopes')
);

// Redirect the user to the authorization URL
return redirect($authUrl);

// In your callback route, exchange the code for tokens
public function handleCallback(Request $request)
{
    $code = $request->get('code');
    
    // Exchange code for tokens
    $tokens = GmailClient::exchangeCode(
        $code, 
        config('gmail-client.redirect_uri')
    );
    
    // Store tokens securely for the authenticated user
    // This is just an example, implement this according to your app's needs
    auth()->user()->update([
        'gmail_access_token' => $tokens['access_token'],
        'gmail_refresh_token' => $tokens['refresh_token'] ?? null,
        'gmail_token_expires_at' => now()->addSeconds($tokens['expires_in']),
    ]);
    
    return redirect()->route('dashboard');
}
```

#### 2. Using built-in routes

If you prefer, you can use the built-in routes for authentication:

1. Enable route registration in the config file:

```php
// config/gmail-client.php
'register_routes' => true,
```

2. Use the provided routes in your app:

```php
// Generate a link to the Gmail authentication page
<a href="{{ route('gmail.auth.redirect') }}">Connect Gmail</a>
```

The package will handle the OAuth flow and store the tokens in the session by default.

### Working with Emails

Once authenticated, you can use the client to interact with Gmail:

#### List Messages

```php
use PartridgeRocks\GmailClient\Facades\GmailClient;

// Authenticate with a stored token
GmailClient::authenticate($accessToken);

// List recent messages (returns a collection of Email data objects)
$messages = GmailClient::listMessages(['maxResults' => 10]);

foreach ($messages as $message) {
    echo $message->subject;
    echo $message->from;
    echo $message->body;
}
```

#### Get a Specific Message

```php
// Get a specific message by ID
$email = GmailClient::getMessage('message-id');

echo $email->subject;
echo $email->from;
echo $email->snippet;
echo $email->body;
```

#### Send an Email

```php
// Send a new email
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Email subject',
    '<p>This is the email body in HTML format.</p>',
    [
        'from' => 'your-email@gmail.com',
        'cc' => 'cc@example.com',
        'bcc' => 'bcc@example.com',
    ]
);

// The sent email object is returned
echo "Email sent with ID: {$email->id}";
```

### Working with Labels

```php
// List all labels
$labels = GmailClient::listLabels();

foreach ($labels as $label) {
    echo $label->name;
    echo $label->id;
}

// Get a specific label
$label = GmailClient::getLabel('label-id');

// Create a new label
$newLabel = GmailClient::createLabel('New Label Name');
```

### Using Without Facade

If you prefer not to use the facade, you can resolve the client from the container:

```php
use PartridgeRocks\GmailClient\GmailClient;

public function index(GmailClient $gmailClient)
{
    $gmailClient->authenticate($accessToken);
    $messages = $gmailClient->listMessages();
    
    // ...
}
```

### Integrating with Your User Model

Here's an example of how you might integrate the Gmail client with your User model:

```php
// In your User model
public function connectGmail($accessToken, $refreshToken = null, $expiresAt = null)
{
    $this->gmail_access_token = $accessToken;
    $this->gmail_refresh_token = $refreshToken;
    $this->gmail_token_expires_at = $expiresAt;
    $this->save();
}

public function getGmailClient()
{
    if (empty($this->gmail_access_token)) {
        throw new \Exception('Gmail not connected');
    }
    
    return app(GmailClient::class)->authenticate(
        $this->gmail_access_token,
        $this->gmail_refresh_token,
        $this->gmail_token_expires_at ? new \DateTime($this->gmail_token_expires_at) : null
    );
}

// In your controller
public function listEmails()
{
    $gmailClient = auth()->user()->getGmailClient();
    return $gmailClient->listMessages(['maxResults' => 20]);
}
```

## Advanced Usage

### Refresh a Token

```php
// Refresh an expired token
$tokens = GmailClient::refreshToken($refreshToken);

// The client is automatically authenticated with the new token
```

### Command Line Testing

The package includes a command to test your Gmail API connection:

```bash
# Get authentication URL
php artisan gmail-client:test --authenticate

# List recent messages
php artisan gmail-client:test --list-messages

# List labels
php artisan gmail-client:test --list-labels
```

## Customization

### Custom Email Templates

You can use your own branded email templates:

```php
// config/gmail-client.php
'branded_template' => resource_path('views/emails/branded-template.blade.php'),
```

## Events

The package dispatches events that you can listen for:

- `GmailAccessTokenRefreshed`: When a token is refreshed
- `GmailMessageSent`: When an email is sent

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jordan Partridge](https://github.com/PartridgeRocks)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.