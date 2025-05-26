# Testing Guide

This document provides comprehensive testing instructions for the Laravel Gmail Client package.

## Unit Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/path/to/TestFile.php

# Run tests with verbose output
./vendor/bin/pest --verbose
```

### Test Structure

- **Unit Tests**: Located in `tests/` directory
- **Test Fixtures**: API response mocks in `tests/fixtures/`
- **Test Helpers**: Mock adapters in `tests/TestHelpers/`

## OAuth Integration Testing

OAuth testing requires a live connection to Google's OAuth endpoints. Follow these steps for comprehensive OAuth flow testing:

### 1. Setup Local Development Environment

#### Option A: Cloudflare Tunnel (Recommended)

```bash
# Install cloudflared
brew install cloudflared

# Start tunnel for your local Laravel app
cloudflared tunnel --url http://localhost:8000
```

#### Option B: ngrok Alternative

```bash
# Install ngrok
brew install ngrok

# Start tunnel
ngrok http 8000
```

### 2. Google Cloud Console Configuration

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create or select a project
3. Enable the Gmail API
4. Go to **APIs & Services > Credentials**
5. Create OAuth 2.0 Client ID credentials
6. Add your tunnel URL to **Authorized redirect URIs**:
   ```
   https://random-tunnel-id.trycloudflare.com/gmail/callback
   ```

### 3. Environment Configuration

Create/update your `.env` file with OAuth credentials:

```bash
# Gmail OAuth Configuration
GMAIL_CLIENT_ID=your_google_client_id_here
GMAIL_CLIENT_SECRET=your_google_client_secret_here
GMAIL_REDIRECT_URI=https://your-tunnel-url.trycloudflare.com/gmail/callback

# Optional: Gmail API Settings
GMAIL_API_VERSION=v1
GMAIL_SCOPES=https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send
```

### 4. OAuth Flow Testing Workflow

#### Start Development Environment

```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Start tunnel
cloudflared tunnel --url http://localhost:8000

# Note the tunnel URL and update your Google Cloud Console redirect URI
```

#### Test OAuth Endpoints

```bash
# Test authorization URL generation
curl -X GET https://your-tunnel-url.trycloudflare.com/gmail/auth

# Test callback handling (after completing OAuth flow)
curl -X GET "https://your-tunnel-url.trycloudflare.com/gmail/callback?code=auth_code_here"

# Test token refresh
curl -X POST https://your-tunnel-url.trycloudflare.com/gmail/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "your_refresh_token"}'
```

#### Manual OAuth Flow Testing

1. Visit the authorization URL in your browser
2. Complete Google OAuth consent flow
3. Verify callback handling and token storage
4. Test API calls with obtained tokens

### 5. Integration Test Examples

```php
// Example OAuth integration test
test('oauth flow completes successfully', function () {
    // Mock OAuth responses
    $this->mockGoogleOAuthResponses();
    
    // Test authorization URL generation
    $authUrl = $this->gmailClient->getAuthorizationUrl();
    expect($authUrl)->toContain('accounts.google.com/o/oauth2/auth');
    
    // Test token exchange
    $tokens = $this->gmailClient->exchangeCodeForTokens('mock_auth_code');
    expect($tokens)->toHaveKeys(['access_token', 'refresh_token']);
    
    // Test API call with tokens
    $messages = $this->gmailClient->listMessages();
    expect($messages)->toBeInstanceOf(Collection::class);
});
```

## API Response Testing

### Mock Responses

The package includes pre-built fixtures for common API responses:

- `tests/fixtures/message.json` - Single email message
- `tests/fixtures/messages-list.json` - List of messages
- `tests/fixtures/label.json` - Single label
- `tests/fixtures/labels-list.json` - List of labels

### Custom Mock Responses

```php
// Create custom mock responses for testing
Http::fake([
    'gmail.googleapis.com/gmail/v1/users/me/messages' => Http::response([
        'messages' => [
            ['id' => 'msg_1', 'threadId' => 'thread_1'],
            ['id' => 'msg_2', 'threadId' => 'thread_2'],
        ],
        'nextPageToken' => 'next_page_token'
    ], 200),
]);
```

## Error Handling Testing

### Authentication Errors

```php
test('handles oauth authentication errors', function () {
    // Test invalid token
    expect(fn() => AuthenticationException::invalidToken())
        ->toThrow(AuthenticationException::class, 'access token is invalid');
    
    // Test OAuth error with exception chaining
    $originalException = new Exception('OAuth provider error');
    $oauthException = AuthenticationException::fromOAuthError(
        'Invalid client credentials',
        $originalException
    );
    
    expect($oauthException->getPrevious())->toBe($originalException);
});
```

### Rate Limiting

```php
test('handles rate limiting gracefully', function () {
    Http::fake([
        'gmail.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'Rate limit exceeded'
            ]
        ], 429, ['Retry-After' => '60']),
    ]);
    
    expect(fn() => $this->gmailClient->listMessages())
        ->toThrow(RateLimitException::class);
});
```

## Performance Testing

### Load Testing

```bash
# Install siege for load testing
brew install siege

# Test OAuth endpoint under load
siege -c 10 -t 30s https://your-app.com/gmail/messages

# Test with authentication headers
siege -c 5 -t 60s -H "Authorization: Bearer your_token" \
  https://your-app.com/gmail/messages
```

### Memory Usage Testing

```php
test('message processing does not leak memory', function () {
    $initialMemory = memory_get_usage();
    
    // Process large number of messages
    for ($i = 0; $i < 1000; $i++) {
        $message = $this->gmailClient->getMessage('msg_' . $i);
        unset($message);
    }
    
    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;
    
    // Assert memory increase is reasonable (less than 10MB)
    expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
});
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    
    - name: Install dependencies
      run: composer install
    
    - name: Run tests
      run: composer test
    
    - name: Run static analysis
      run: composer analyse
```

## Debugging OAuth Issues

### Common Issues and Solutions

1. **Invalid Redirect URI**
   ```
   Error: redirect_uri_mismatch
   Solution: Ensure redirect URI in Google Console matches exactly
   ```

2. **Invalid Client Credentials**
   ```
   Error: invalid_client
   Solution: Verify GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET
   ```

3. **Scope Issues**
   ```
   Error: insufficient_scope
   Solution: Ensure required scopes are included in authorization URL
   ```

### Debug Logging

Enable detailed logging for OAuth debugging:

```php
// In your test environment
Log::debug('OAuth Request', [
    'client_id' => config('gmail-client.client_id'),
    'redirect_uri' => config('gmail-client.redirect_uri'),
    'scopes' => config('gmail-client.scopes'),
]);
```