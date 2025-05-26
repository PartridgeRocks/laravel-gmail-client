# Gmail Client Usage Guide

This document provides detailed examples of how to use the PartridgeRocks Laravel Gmail Client.

## Table of Contents

- [Basic Setup](#basic-setup)
- [Authentication](#authentication)
- [Performance Features](#performance-features)
- [Working with Messages](#working-with-messages)
- [Working with Labels](#working-with-labels)
- [Error Handling](#error-handling)
- [Pagination](#pagination)
- [Testing](#testing)

## Basic Setup

First, install the package via Composer:

```bash
composer require partridgerocks/gmail-client
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="gmail-client-config"
```

Add your Gmail API credentials to your `.env` file:

```
GMAIL_CLIENT_ID=your-client-id
GMAIL_CLIENT_SECRET=your-client-secret
GMAIL_REDIRECT_URI=https://your-app.com/gmail/auth/callback
GMAIL_FROM_EMAIL=your-email@gmail.com

# Performance settings
GMAIL_SMART_COUNTING=true
GMAIL_COUNT_THRESHOLD=50
GMAIL_API_TIMEOUT=30
```

### Using the Facade

The package provides a convenient facade for accessing the Gmail client functionality:

```php
use PartridgeRocks\GmailClient\Facades\GmailClient;

// List messages
$messages = GmailClient::listMessages();

// Get a single message
$message = GmailClient::getMessage('message-id');
```

The facade is automatically registered by the package's service provider, so you can use it right away in your Laravel application.

## Authentication

### OAuth Flow

The Gmail Client uses OAuth 2.0 for authentication. Here's a complete example of implementing the OAuth flow:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PartridgeRocks\GmailClient\Facades\GmailClient;

class GmailAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     */
    public function redirect()
    {
        // Build the authorization URL with required scopes
        $authUrl = GmailClient::getAuthorizationUrl(
            config('gmail-client.redirect_uri'),
            config('gmail-client.scopes'),
            [
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]
        );
        
        // Store state in session for validation on callback
        session(['gmail_oauth_state' => $state]);
        
        // Redirect user to Google's OAuth page
        return redirect($authUrl);
    }
    
    /**
     * Handle the OAuth callback
     */
    public function callback(Request $request)
    {
        // Validate state to prevent CSRF attacks
        if ($request->has('error')) {
            return redirect()->route('home')->with('error', 'Failed to connect Google account: ' . $request->get('error'));
        }
        
        try {
            // Exchange authorization code for access token
            $tokens = GmailClient::exchangeCode(
                $request->get('code'),
                config('gmail-client.redirect_uri')
            );
            
            // Calculate token expiration time
            $expiresAt = now()->addSeconds($tokens['expires_in']);
            
            // Store tokens in your user model or other storage
            auth()->user()->update([
                'gmail_access_token' => $tokens['access_token'],
                'gmail_refresh_token' => $tokens['refresh_token'] ?? null,
                'gmail_token_expires_at' => $expiresAt
            ]);
            
            return redirect()->route('dashboard')->with('success', 'Gmail account connected successfully');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Failed to connect Gmail account: ' . $e->getMessage());
        }
    }
}
```

### Token Management

It's important to handle token refreshing. Here's an example implementation with a middleware:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PartridgeRocks\GmailClient\Facades\GmailClient;
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;

class RefreshGmailToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Skip if user doesn't have Gmail connected
        if (!$user || !$user->gmail_access_token) {
            return $next($request);
        }
        
        try {
            // Check if token is expired
            if ($user->gmail_token_expires_at && now()->isAfter($user->gmail_token_expires_at)) {
                // No refresh token available
                if (!$user->gmail_refresh_token) {
                    throw new AuthenticationException('No refresh token available');
                }
                
                // Refresh the token
                $tokens = GmailClient::refreshToken($user->gmail_refresh_token);
                
                // Update tokens in database
                $user->update([
                    'gmail_access_token' => $tokens['access_token'],
                    'gmail_refresh_token' => $tokens['refresh_token'] ?? $user->gmail_refresh_token,
                    'gmail_token_expires_at' => now()->addSeconds($tokens['expires_in']),
                ]);
            }
            
            // Authenticate the client with the current access token
            GmailClient::authenticate($user->gmail_access_token);
            
        } catch (\Exception $e) {
            // Handle authentication errors (log, clear tokens, etc.)
            logger()->error('Gmail token refresh failed: ' . $e->getMessage());
            
            // Optionally clear invalid tokens
            // $user->update([
            //     'gmail_access_token' => null,
            //     'gmail_refresh_token' => null,
            //     'gmail_token_expires_at' => null,
            // ]);
        }
        
        return $next($request);
    }
}
```

## Performance Features

### Account Statistics (Batch Retrieval)

For multi-account Gmail applications or dashboards, use the optimized `getAccountStatistics()` method to minimize API calls and improve performance:

```php
// Get comprehensive account metrics efficiently
$stats = GmailClient::getAccountStatistics([
    'unread_limit' => 25,           // Show exact count up to 25, then "25+"
    'today_limit' => 15,            // Today's messages limit
    'include_labels' => true,       // Include total label count
    'estimate_large_counts' => true, // Use smart estimation for performance
    'background_mode' => false,     // Throw exceptions or return partial data
    'timeout' => 30,                // Request timeout in seconds
]);

// Returns a comprehensive statistics array:
// [
//     'unread_count' => 23,           // Exact count or "25+" for large counts
//     'today_count' => 8,             // Today's messages
//     'labels_count' => 42,           // Total labels
//     'estimated_total' => 15000,     // Total mailbox size estimate
//     'api_calls_made' => 2,          // Actual API calls used
//     'last_updated' => '2024-01-01T12:00:00Z',
//     'partial_failure' => false,     // True if some metrics failed
//     'unread_estimate' => 15000,     // Gmail's estimate for unread count
// ]

// Example usage in a dashboard
foreach ($user->gmailAccounts as $account) {
    try {
        $client = new GmailClient($account->access_token);
        $stats = $client->getAccountStatistics();
        
        echo "Account: {$account->email}\n";
        echo "Unread: {$stats['unread_count']}\n";
        echo "Today: {$stats['today_count']}\n";
        echo "Labels: {$stats['labels_count']}\n";
        echo "API calls used: {$stats['api_calls_made']}\n";
        
    } catch (\Exception $e) {
        // Handle errors gracefully
        logger()->warning("Failed to get stats for {$account->email}: {$e->getMessage()}");
    }
}
```

### Performance Benefits

**Before optimization:**
- 3-5 API calls per account for basic stats
- 2-5 second load time for 2 accounts
- Risk of hitting rate limits
- Timeout issues with large mailboxes

**After optimization:**
- 1-2 API calls per account for basic stats
- <1 second load time with smart estimation
- Graceful degradation under load
- Smart estimation prevents timeouts

### Connection Health Monitoring

Monitor Gmail connection status and API quotas:

```php
$health = GmailClient::getAccountHealth();

// Returns comprehensive health information:
// [
//     'connected' => true,
//     'status' => 'healthy',           // healthy, unhealthy, rate_limited, etc.
//     'api_quota_remaining' => 250,    // Remaining API calls (if available)
//     'last_successful_call' => '2024-01-01T12:00:00Z',
//     'errors' => [],                  // Array of error messages if any
// ]

// Use for health monitoring in dashboards
switch ($health['status']) {
    case 'healthy':
        $statusIcon = '🟢';
        break;
    case 'rate_limited':
        $statusIcon = '🟡';
        break;
    case 'authentication_failed':
        $statusIcon = '🔴';
        break;
    default:
        $statusIcon = '⚪';
}

echo "Gmail Status: {$statusIcon} {$health['status']}\n";

if (isset($health['api_quota_remaining'])) {
    echo "API Quota Remaining: {$health['api_quota_remaining']}\n";
}
```

### Background Processing

For even better performance, process statistics in background jobs:

```php
// In your controller
dispatch(new RefreshGmailStatsJob($user));

// Background job
class RefreshGmailStatsJob implements ShouldQueue
{
    public function handle()
    {
        $stats = GmailClient::getAccountStatistics([
            'background_mode' => true,  // Don't throw exceptions
            'timeout' => 60,           // Longer timeout for background processing
        ]);
        
        // Store stats in cache or database for quick retrieval
        Cache::put("gmail_stats_{$this->user->id}", $stats, now()->addMinutes(5));
    }
}
```

## Working with Messages

### Listing Messages

#### Standard Method

```php
// Basic listing (loads all message details in memory at once)
$messages = GmailClient::listMessages(['maxResults' => 10]);

// With query filters (search)
$messages = GmailClient::listMessages([
    'q' => 'from:example@gmail.com after:2023/01/01 has:attachment',
    'maxResults' => 20
]);

// Process messages
foreach ($messages as $message) {
    echo "From: {$message->from}\n";
    echo "Subject: {$message->subject}\n";
    echo "Date: {$message->internalDate->format('Y-m-d H:i:s')}\n";
    echo "Snippet: {$message->snippet}\n";
    echo "-----------------------\n";
}
```

#### Memory-Efficient Lazy Loading (Recommended for Large Datasets)

```php
// Create a lazy-loading collection of messages
$messages = GmailClient::listMessages(
    query: ['q' => 'is:unread'], 
    lazy: true,
    maxResults: 100,
    fullDetails: true
);

// Messages are loaded only when accessed, keeping memory usage low
foreach ($messages as $message) {
    echo "From: {$message->from}\n";
    echo "Subject: {$message->subject}\n";
    
    // You can stop iteration at any point
    if ($someCondition) {
        break;
    }
}

// You can also use Laravel Collection methods
$importantMessages = $messages
    ->filter(function ($message) {
        return in_array('IMPORTANT', $message->labelIds);
    })
    ->take(5);
```

For very large datasets where you only need basic metadata:

```php
// Get only message IDs and thread IDs without full details
$messageIds = GmailClient::listMessages(
    lazy: true,
    fullDetails: false
);

foreach ($messageIds as $messageData) {
    echo "Message ID: {$messageData['id']}\n";
    
    // Load full details only for specific messages if needed
    if ($needsFullDetails) {
        $fullMessage = GmailClient::getMessage($messageData['id']);
    }
}
```

### Getting a Single Message

```php
$message = GmailClient::getMessage('message-id');

// Access all message data
echo "From: {$message->from}\n";
echo "To: " . implode(', ', $message->to) . "\n";
echo "Subject: {$message->subject}\n";
echo "Date: {$message->internalDate->format('Y-m-d H:i:s')}\n";
echo "Body: {$message->body}\n";

// Access all headers
foreach ($message->headers as $name => $value) {
    echo "{$name}: {$value}\n";
}

// Access specific headers
$messageId = $message->headers['Message-ID'] ?? null;
$replyTo = $message->headers['Reply-To'] ?? null;
```

### Sending Messages

```php
// Simple email
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Test email from Laravel Gmail Client',
    '<p>This is a <strong>test</strong> email sent using Laravel Gmail Client.</p>'
);

// Email with options
$email = GmailClient::sendEmail(
    'recipient@example.com',
    'Email with options',
    '<p>This email includes CC and BCC recipients.</p>',
    [
        'from' => 'your-email@gmail.com',
        'cc' => 'cc-recipient@example.com',
        'bcc' => 'bcc-recipient@example.com',
    ]
);

// The sent email is returned
echo "Email sent with ID: {$email->id}";
```

## Working with Labels

### Listing Labels

```php
// Get all labels
$labels = GmailClient::listLabels();

// Display label information
foreach ($labels as $label) {
    echo "Label: {$label->name} (ID: {$label->id})\n";
    echo "Type: {$label->type}\n";
    
    if ($label->messagesTotal !== null) {
        echo "Messages: {$label->messagesTotal} ({$label->messagesUnread} unread)\n";
    }
    
    echo "-----------------------\n";
}
```

### Creating and Modifying Labels

```php
// Create a new label
$newLabel = GmailClient::createLabel('Important Clients', [
    'labelListVisibility' => 'labelShow',
    'messageListVisibility' => 'show',
    'color' => [
        'backgroundColor' => '#16a765',
        'textColor' => '#ffffff'
    ]
]);

// Retrieve a specific label
$label = GmailClient::getLabel('Label_123');

// Update a label (using the LabelResource directly)
$updatedLabel = GmailClient->labels()->update($label->id, [
    'name' => 'VIP Clients',
    'color' => [
        'backgroundColor' => '#4986e7',
        'textColor' => '#ffffff'
    ]
]);

// Delete a label (using the LabelResource directly)
GmailClient->labels()->delete($label->id);
```

## Error Handling

The package provides specific exception types that you can catch and handle appropriately:

```php
use PartridgeRocks\GmailClient\Exceptions\AuthenticationException;
use PartridgeRocks\GmailClient\Exceptions\NotFoundException;
use PartridgeRocks\GmailClient\Exceptions\RateLimitException;
use PartridgeRocks\GmailClient\Exceptions\ValidationException;
use PartridgeRocks\GmailClient\Exceptions\GmailClientException;

try {
    // Try to get a message or perform any other Gmail operation
    $message = GmailClient::getMessage('non-existent-id');
} catch (AuthenticationException $e) {
    // Handle authentication errors
    logger()->error('Gmail authentication error: ' . $e->getMessage());
    
    // Check specific error types
    if ($e->getError() && $e->getError()->code === 'token_expired') {
        // Handle expired token
    }
    
    // Redirect to reconnect Gmail account
    return redirect()->route('gmail.auth.redirect')
        ->with('error', 'Your Gmail connection has expired. Please reconnect your account.');
        
} catch (NotFoundException $e) {
    // Handle not found errors
    return back()->with('error', 'The requested email was not found.');
    
} catch (RateLimitException $e) {
    // Handle rate limit errors
    $retryAfter = $e->getRetryAfter();
    
    logger()->warning("Gmail API rate limit exceeded. Retry after {$retryAfter} seconds.");
    
    return back()->with('error', 
        "You've reached the Gmail API rate limit. Please try again later.");
        
} catch (ValidationException $e) {
    // Handle validation errors
    return back()->with('error', 'Invalid data: ' . $e->getMessage());
    
} catch (GmailClientException $e) {
    // Handle other Gmail client errors
    logger()->error('Gmail client error: ' . $e->getMessage());
    
    return back()->with('error', 'There was an error communicating with Gmail.');
    
} catch (\Exception $e) {
    // Handle any other unexpected errors
    logger()->error('Unexpected error: ' . $e->getMessage());
    
    return back()->with('error', 'An unexpected error occurred.');
}
```

## Pagination

When dealing with large numbers of messages or labels, you can use pagination:

```php
// Enable pagination for messages
$paginator = GmailClient::listMessages(['maxResults' => 25], true);

// Get messages page by page
$currentPage = 1;

while ($paginator->hasMorePages() && $currentPage <= 5) {
    // Get the next page of messages
    $messages = $paginator->getNextPage();
    
    echo "Page {$currentPage}:\n";
    
    foreach ($messages as $message) {
        echo "- {$message['id']}: {$message['snippet']}\n";
    }
    
    $currentPage++;
}

// Transform all paged results into DTOs
use PartridgeRocks\GmailClient\Data\Responses\EmailDTO;

$labelPaginator = GmailClient::listLabels(true, 50);
$allLabels = $labelPaginator->transformUsingDTO(LabelDTO::class);
```

## Memory Efficiency Considerations

When working with large Gmail accounts, it's important to avoid loading all messages into memory at once. 
The package provides several options for efficient memory usage:

1. **Lazy Loading**: This is the most memory-efficient approach for iterating through large collections
   ```php
   $messages = GmailClient::listMessages(lazy: true);
   ```

2. **Pagination**: For manual control of page loading
   ```php
   $paginator = GmailClient::listMessages(paginate: true);
   ```

3. **Metadata Only**: When you only need message IDs and thread IDs
   ```php
   $messageIds = GmailClient::listMessages(lazy: true, fullDetails: false);
   ```

## Testing

When testing your application, you can use Saloon's built-in mocking capabilities:

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

// Now your tests will use the mocked responses
public function test_it_can_list_gmail_messages()
{
    $this->actingAs($user);
    
    $response = $this->get('/messages');
    
    $response->assertStatus(200);
    $response->assertSee('This is a test email');
}
```

---

For more information, check out the [official documentation](https://github.com/partridgerocks/gmail-client) or the [Gmail API documentation](https://developers.google.com/gmail/api).