# Laravel Gmail Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/partridgerocks/gmail-client.svg?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/partridgerocks/laravel-gmail-client/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/partridgerocks/laravel-gmail-client/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/partridgerocks/laravel-gmail-client/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/partridgerocks/laravel-gmail-client/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/partridgerocks/gmail-client.svg?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)
[![PHP Version Support](https://img.shields.io/packagist/php-v/partridgerocks/gmail-client.svg?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)
[![Laravel Version Support](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-orange?style=flat-square)](https://packagist.org/packages/partridgerocks/gmail-client)

A Laravel package that integrates with the Gmail API to seamlessly manage emails within your application. Built with [Saloon PHP](https://github.com/saloonphp/saloon) for API interactions and [Laravel Data](https://github.com/spatie/laravel-data) for structured data handling.

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Google API Setup](#-google-api-setup)
- [Usage](#-usage)
  - [Authentication](#authentication)
  - [Working with Emails](#working-with-emails)
  - [Contact Parsing & CRM Integration](#contact-parsing--crm-integration)
  - [Working with Labels](#working-with-labels)
  - [Using Without Facade](#using-without-facade)
  - [Integration with User Model](#integration-with-your-user-model)
- [Advanced Usage](#-advanced-usage)
  - [Pagination](#pagination-support)
  - [Memory-Efficient Processing](#memory-efficiency)
  - [Null-Safe Methods](#null-safe-methods-for-robust-applications)
  - [Error Handling](#enhanced-error-handling)
  - [Token Refreshing](#refresh-a-token)
  - [CLI Testing](#command-line-testing)
  - [Custom Templates](#custom-email-templates)
- [Configuration](#-configuration)
- [Events](#-events)
- [Testing](#-testing)
- [Changelog](#-changelog)
- [Contributing](#-contributing)
- [Security](#-security-vulnerabilities)
- [Credits](#-credits)
- [License](#-license)

## 🚀 Features

- **OAuth Authentication**: Seamless integration with Gmail's OAuth 2.0 flow
- **Email Operations**:
  - Read emails and threads with full content and attachments
  - Send emails with HTML content
  - Support for CC, BCC, and custom sender addresses
  - Automatic contact parsing for CRM integration
- **Label Management**:
  - List, create, update, and delete email labels
  - Organize emails with custom label hierarchies
- **Performance Optimizations**:
  - Batch statistics retrieval for multi-account dashboards
  - Smart count estimation to prevent timeouts on large mailboxes
  - Connection health monitoring with quota tracking
  - Lazy loading collections for memory-efficient processing
  - Pagination support for large datasets
  - Customizable batch sizes for API requests
- **Developer Experience**:
  - Laravel facade for convenient access
  - Strongly-typed data objects with Laravel Data
  - Full Laravel service container integration
  - Comprehensive exception handling
  - Null-safe methods for robust applications (dashboards, background processing)
  - Command-line testing utilities
- **Enterprise Architecture**:
  - Service layer with interface contracts for enhanced testability
  - Builder pattern for fluent client construction
  - Repository pattern for data access abstraction
  - Factory pattern for centralized service management
  - Type-safe configuration objects
  - Comprehensive test data builders and mock factories

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Google API credentials

## 📦 Installation

You can install the package via Composer:

```bash
composer require partridgerocks/gmail-client
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --tag="gmail-client-config"
```

This will create a `config/gmail-client.php` configuration file in your project.

## 🔐 Google API Setup

Before you can use the Gmail Client, you need to set up a project in the Google Developer Console and obtain OAuth 2.0 credentials:

1. Go to the [Google Developer Console](https://console.developers.google.com/)
2. Create a new project (or select an existing one)
3. Navigate to "APIs & Services" > "Library"
4. Search for and enable the "Gmail API"
5. Go to "APIs & Services" > "Credentials"
6. Click "Configure Consent Screen" and set up your OAuth consent screen:
   - Select "External" or "Internal" user type (depending on your needs)
   - Fill in the required app information
   - Add the required scopes (see below)
   - Add test users if needed (for external user type)
7. Create OAuth 2.0 credentials:
   - Go to "Credentials" and click "Create Credentials" > "OAuth client ID"
   - Select "Web application" as the application type
   - Add a name for your client
   - Add your authorized redirect URIs (this should match your `GMAIL_REDIRECT_URI` config value)
   - Click "Create"
8. Copy the Client ID and Client Secret to your `.env` file:

```
GMAIL_CLIENT_ID=your-client-id
GMAIL_CLIENT_SECRET=your-client-secret
GMAIL_REDIRECT_URI=https://your-app.com/gmail/auth/callback
GMAIL_FROM_EMAIL=your-email@gmail.com

# Performance optimization settings
GMAIL_SMART_COUNTING=true
GMAIL_COUNT_THRESHOLD=50
GMAIL_CACHE_TTL=300
GMAIL_MAX_CONCURRENT=3
GMAIL_CIRCUIT_BREAKER=true
GMAIL_API_TIMEOUT=30

# Multi-account settings
GMAIL_MAX_ACCOUNTS=5
GMAIL_HEALTH_CHECK_INTERVAL=3600
GMAIL_BULK_OPERATIONS=true
```

> **Note**: The Gmail API requires specific scopes to access different features. The default configuration includes commonly used scopes, but you can customize them in the config file.

## 🔍 Usage

The package automatically registers a Laravel Facade that provides a convenient way to interact with the Gmail API:

```php
use PartridgeRocks\GmailClient\Facades\GmailClient;

// Get recent messages
$messages = GmailClient::listMessages();

// Get a specific message
$message = GmailClient::getMessage('message-id');

// Send an email
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Subject line',
    '<p>Email body in HTML format</p>'
);
```

### Authentication

The Gmail Client provides two ways to authenticate with the Gmail API:

#### 1. Manual Authentication Flow

If you want full control over the authentication process:

```php
use PartridgeRocks\GmailClient\Facades\GmailClient;

// 1. Get the authorization URL
$authUrl = GmailClient::getAuthorizationUrl(
    config('gmail-client.redirect_uri'),
    config('gmail-client.scopes'),
    [
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]
);

// 2. Redirect the user to the authorization URL
return redirect($authUrl);

// 3. In your callback route, exchange the code for tokens
public function handleCallback(Request $request)
{
    $code = $request->get('code');

    // Exchange code for tokens
    $tokens = GmailClient::exchangeCode(
        $code,
        config('gmail-client.redirect_uri')
    );

    // Store tokens securely for the authenticated user
    auth()->user()->update([
        'gmail_access_token' => $tokens['access_token'],
        'gmail_refresh_token' => $tokens['refresh_token'] ?? null,
        'gmail_token_expires_at' => now()->addSeconds($tokens['expires_in']),
    ]);

    return redirect()->route('dashboard');
}
```

#### 2. Using Built-in Routes

For a simpler setup, you can enable the built-in routes:

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
    echo "From: {$message->from}\n";
    echo "Subject: {$message->subject}\n";
    echo "Date: {$message->internalDate->format('Y-m-d H:i:s')}\n";
    echo "Body: {$message->body}\n";
}

// With query parameters (using Gmail search syntax)
$messages = GmailClient::listMessages([
    'q' => 'from:example@gmail.com after:2023/01/01 has:attachment',
    'maxResults' => 20
]);
```

#### Get a Specific Message

```php
// Get a specific message by ID
$email = GmailClient::getMessage('message-id');

echo "Subject: {$email->subject}\n";
echo "From: {$email->from}\n";
echo "Snippet: {$email->snippet}\n";
echo "Body: {$email->body}\n";

// Access message headers
foreach ($email->headers as $name => $value) {
    echo "{$name}: {$value}\n";
}

// Check for specific labels
if (in_array('INBOX', $email->labelIds)) {
    echo "This message is in the inbox\n";
}
```

#### Send an Email

```php
// Send a simple email
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Email subject',
    '<p>This is the email body in HTML format.</p>'
);

// Send with additional options
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Email with options',
    '<p>This email includes CC and BCC recipients.</p>',
    [
        'from' => 'your-email@gmail.com',
        'cc' => 'cc@example.com',
        'bcc' => 'bcc@example.com',
    ]
);

// The sent email object is returned
echo "Email sent with ID: {$email->id}";
```

### Performance Features

#### Account Statistics (Batch Retrieval)

For multi-account dashboards, use the batch statistics method to minimize API calls:

```php
// Get comprehensive account metrics in 1-2 API calls
$stats = GmailClient::getAccountStatistics([
    'unread_limit' => 25,        // Show exact count up to 25, then "25+"
    'today_limit' => 15,         // Today's messages limit  
    'include_labels' => true,    // Include label count
    'estimate_large_counts' => true,  // Use smart estimation
    'background_mode' => false,  // Throw exceptions or return partial data
]);

// Returns:
// [
//     'unread_count' => 23,           // or "25+" for large counts
//     'today_count' => 8,             // Today's messages
//     'labels_count' => 42,           // Total labels
//     'estimated_total' => 15000,     // Total mailbox size estimate
//     'api_calls_made' => 2,          // Actual API calls used
//     'last_updated' => '2024-01-01T12:00:00Z',
//     'partial_failure' => false,     // True if some metrics failed
// ]

// Performance comparison:
// Before: 3-5 API calls per account, 2-5s load time
// After:  1-2 API calls per account, <1s load time
```

#### Connection Health Monitoring

Monitor connection status and API quota:

```php
$health = GmailClient::getAccountHealth();

// Returns:
// [
//     'connected' => true,
//     'status' => 'healthy',           // healthy, unhealthy, rate_limited, etc.
//     'api_quota_remaining' => 250,    // Remaining API calls (if available)
//     'last_successful_call' => '2024-01-01T12:00:00Z',
//     'errors' => [],                  // Array of error messages
// ]

// Use for dashboard health indicators
if ($health['status'] === 'rate_limited') {
    // Handle rate limiting gracefully
    $retryAfter = $health['retry_after'] ?? 60;
}
```

### Contact Parsing & CRM Integration

The package automatically parses email addresses and names from Gmail messages, making it easy to integrate with CRM systems:

```php
// Get an email message
$email = GmailClient::getMessage('message-id');

// Access parsed contact information
$sender = $email->fromContact;
echo "Sender: {$sender->name} <{$sender->email}>\n";
echo "Domain: {$sender->domain}\n";

// Access recipient contacts
foreach ($email->toContacts as $contact) {
    echo "To: {$contact->getDisplayName()} - {$contact->email}\n";
}

// Get all contacts involved in the email
$allContacts = $email->getAllContacts();

// Find contacts from specific domain (useful for CRM)
$externalContacts = array_filter(
    $allContacts,
    fn($contact) => !$contact->isFromDomain('mycompany.com')
);

// Get unique domains for company identification
$domains = $email->getContactDomains();
// Returns: ['example.com', 'client.com', 'mycompany.com']

// Check if email involves specific company
if ($email->hasContactFromDomain('important-client.com')) {
    // Handle VIP client email
}

// Get all contacts from a domain
$clientContacts = $email->getContactsFromDomain('acme-corp.com');
```

#### Manual Contact Parsing

You can also parse email strings manually:

```php
use PartridgeRocks\GmailClient\Data\Contact;

// Parse single email address
$contact = Contact::parse('"John Doe" <john@example.com>');
echo $contact->name;    // "John Doe"  
echo $contact->email;   // "john@example.com"
echo $contact->domain;  // "example.com"

// Parse multiple addresses
$contacts = Contact::parseMultiple('john@example.com, "Jane Doe" <jane@example.com>');

// Access contact properties
foreach ($contacts as $contact) {
    echo "Name: " . ($contact->name ?? 'No name') . "\n";
    echo "Email: {$contact->email}\n";
    echo "Domain: {$contact->domain}\n";
    echo "Local part: {$contact->getLocalPart()}\n";
    echo "Display name: {$contact->getDisplayName()}\n";
    echo "Formatted: {$contact->format()}\n";
}
```

#### CRM Integration Examples

```php
// Find all emails from a specific company
$companyEmails = collect($emails)->filter(function ($email) {
    return $email->hasContactFromDomain('target-company.com');
});

// Extract contact data for CRM import
$crmData = [];
foreach ($emails as $email) {
    foreach ($email->getAllContacts() as $contact) {
        if (!$contact->isFromDomain('mycompany.com')) {
            $crmData[] = [
                'name' => $contact->name,
                'email' => $contact->email,
                'company_domain' => $contact->domain,
                'first_contact' => $email->internalDate,
            ];
        }
    }
}

// Group contacts by company domain
$contactsByCompany = collect($emails)
    ->flatMap(fn($email) => $email->getAllContacts())
    ->groupBy('domain')
    ->map(fn($contacts) => $contacts->unique('email'));
```

### Working with Labels

Gmail uses labels to organize emails. You can create, retrieve, and manage these labels:

```php
// List all labels
$labels = GmailClient::listLabels();

foreach ($labels as $label) {
    echo "Label: {$label->name} (ID: {$label->id})\n";
    echo "Type: {$label->type}\n";
    
    if ($label->messagesTotal !== null) {
        echo "Messages: {$label->messagesTotal} ({$label->messagesUnread} unread)\n";
    }
}

// Get a specific label
$label = GmailClient::getLabel('label-id');

// Create a new label
$newLabel = GmailClient::createLabel('Important Clients', [
    'labelListVisibility' => 'labelShow',
    'messageListVisibility' => 'show',
    'color' => [
        'backgroundColor' => '#16a765',
        'textColor' => '#ffffff'
    ]
]);

// Update a label (using the LabelResource directly)
$updatedLabel = GmailClient::labels()->update($label->id, [
    'name' => 'VIP Clients',
    'color' => [
        'backgroundColor' => '#4986e7',
        'textColor' => '#ffffff'
    ]
]);

// Delete a label
GmailClient::labels()->delete($label->id);
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

### Builder Pattern & Service Integration

For advanced usage with dependency injection and configuration:

```php
use PartridgeRocks\GmailClient\Builders\GmailClientBuilder;
use PartridgeRocks\GmailClient\Contracts\MessageServiceInterface;

// Using builder pattern
$client = GmailClientBuilder::create()
    ->withToken($accessToken)
    ->withConfig($customConfig)
    ->build();

// Using service injection
class EmailController extends Controller
{
    public function __construct(private MessageServiceInterface $messageService) {}
    
    public function dashboard()
    {
        $recentMessages = $this->messageService->findRecent(10);
        return view('dashboard', compact('recentMessages'));
    }
}
```

### Integration with Your User Model

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

## 🔧 Advanced Usage

### Pagination Support

The package supports pagination for listing messages and labels:

```php
// Get a paginator for messages
$paginator = GmailClient::listMessages(['maxResults' => 25], true);

// Get the first page
$firstPage = $paginator->getNextPage();

// Check if there are more pages
if ($paginator->hasMorePages()) {
    // Get the next page
    $secondPage = $paginator->getNextPage();
}

// Or get all pages at once (use cautiously with large datasets)
$allMessages = $paginator->getAllPages();

// You can also transform the results using the DTO
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;
$emails = $paginator->transformUsingDTO(EmailDTO::class);
```

### Memory Efficiency

When working with large Gmail accounts, it's important to avoid loading all messages into memory at once:

```php
// Lazy loading is the most memory-efficient approach for large datasets
$messages = GmailClient::listMessages(lazy: true);

// Process messages one by one without loading everything into memory
foreach ($messages as $message) {
    processMessage($message);
    
    // You can stop iteration at any point
    if ($someCondition) {
        break;
    }
}

// For even more efficiency, you can get only message IDs without full details
$messageIds = GmailClient::listMessages(lazy: true, fullDetails: false);

foreach ($messageIds as $messageData) {
    echo "Message ID: {$messageData['id']}\n";
    
    // Load full details only for specific messages if needed
    if (needsFullDetails($messageData)) {
        $fullMessage = GmailClient::getMessage($messageData['id']);
    }
}
```

### Null-Safe Methods for Robust Applications

For applications that need graceful degradation (dashboards, background processing), use the null-safe methods that never throw exceptions:

```php
// ✅ Safe for dashboards - returns empty collection on any error
$labels = GmailClient::safeListLabels();
$messages = GmailClient::safeListMessages(['q' => 'is:unread']);

// ✅ Returns null instead of throwing NotFoundException
$message = GmailClient::safeGetMessage('message-id');

// ✅ Connection health check - never throws exceptions
if (GmailClient::isConnected()) {
    // Safe to proceed with Gmail operations
}

// ✅ Account overview with fallback data
$summary = GmailClient::getAccountSummary();
// Returns: ['connected' => true, 'labels_count' => 15, 'has_unread' => true, 'errors' => []]

// ✅ Statistics with graceful degradation
$stats = GmailClient::safeGetAccountStatistics();
// Returns fallback data if API fails: ['unread_count' => '?', 'partial_failure' => true]
```

**Perfect for:**
- Dashboard widgets that should never crash
- Background sync processes
- Health monitoring systems
- Any UI requiring robust error handling

### Enhanced Error Handling

The package provides detailed error handling for common Gmail API errors:

```php
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;

try {
    $message = GmailClient::getMessage('non-existent-id');
} catch (NotFoundException $e) {
    // Handle not found error
    echo "Message not found: " . $e->getMessage();
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication error: " . $e->getMessage();

    if ($e->getError()->code === 'token_expired') {
        // Refresh the token
        $tokens = GmailClient::refreshToken($refreshToken);
    }
} catch (RateLimitException $e) {
    // Handle rate limit errors
    $retryAfter = $e->getRetryAfter();
    echo "Rate limit exceeded. Retry after {$retryAfter} seconds.";
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation error: " . $e->getMessage();
}
```

### Refresh a Token

```php
// Refresh an expired token
$tokens = GmailClient::refreshToken($refreshToken);

// The client is automatically authenticated with the new token
// Update the tokens in your storage
$user->update([
    'gmail_access_token' => $tokens['access_token'],
    'gmail_refresh_token' => $tokens['refresh_token'] ?? $user->gmail_refresh_token,
    'gmail_token_expires_at' => now()->addSeconds($tokens['expires_in']),
]);
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

### Custom Email Templates

You can use your own branded email templates:

```php
// config/gmail-client.php
'branded_template' => resource_path('views/emails/branded-template.blade.php'),
```

## ⚙️ Configuration

The package configuration file (`config/gmail-client.php`) provides extensive customization options:

### Performance Settings

```php
'performance' => [
    'enable_smart_counting' => true,           // Enable smart count estimation
    'count_estimation_threshold' => 50,        // Show exact count up to this limit
    'default_cache_ttl' => 300,               // Cache TTL in seconds (future use)
    'max_concurrent_requests' => 3,           // Max concurrent API requests (future use)
    'enable_circuit_breaker' => true,         // Enable circuit breaker pattern (future use)
    'api_timeout' => 30,                      // API request timeout in seconds
],
```

### Multi-Account Settings

```php
'multi_account' => [
    'max_accounts_per_user' => 5,             // Maximum Gmail accounts per user
    'health_check_interval' => 3600,          // Health check interval in seconds
    'enable_bulk_operations' => true,         // Enable bulk operations (future use)
],
```

### Environment Variables

All configuration options can be overridden via environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `GMAIL_SMART_COUNTING` | `true` | Enable smart count estimation |
| `GMAIL_COUNT_THRESHOLD` | `50` | Threshold for showing exact vs estimated counts |
| `GMAIL_CACHE_TTL` | `300` | Cache time-to-live in seconds |
| `GMAIL_MAX_CONCURRENT` | `3` | Maximum concurrent requests |
| `GMAIL_CIRCUIT_BREAKER` | `true` | Enable circuit breaker pattern |
| `GMAIL_API_TIMEOUT` | `30` | API request timeout in seconds |
| `GMAIL_MAX_ACCOUNTS` | `5` | Maximum accounts per user |
| `GMAIL_HEALTH_CHECK_INTERVAL` | `3600` | Health check interval in seconds |
| `GMAIL_BULK_OPERATIONS` | `true` | Enable bulk operations |

## 📡 Events

The package dispatches events that you can listen for:

- `GmailAccessTokenRefreshed`: When a token is refreshed
- `GmailMessageSent`: When an email is sent

## 🧪 Testing

The package includes tests that you can run with PHPUnit:

```bash
composer test
```

For testing in your own application, you can use Saloon's built-in mocking capabilities:

```php
use Saloon\Laravel\Facades\Saloon;
use Saloon\Http\Faking\MockResponse;

// In your test setup
public function setUp(): void
{
    parent::setUp();
    
    // Mock all Gmail API responses
    Saloon::fake([
        '*gmail.googleapis.com*' => MockResponse::make([
            'messages' => [
                [
                    'id' => 'test-id-123',
                    'threadId' => 'thread-123',
                    'snippet' => 'This is a test email',
                ]
            ]
        ], 200),
    ]);
}
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 👨‍💻 Credits

- [Jordan Partridge](https://github.com/PartridgeRocks)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.