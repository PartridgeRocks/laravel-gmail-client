# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Gmail Client is a package that integrates with the Gmail API to seamlessly manage emails within Laravel applications. The package is built with [Saloon PHP](https://github.com/saloonphp/saloon) for API interactions and [Laravel Data](https://github.com/spatie/laravel-data) for data structures.

## Key Features

- OAuth authentication with Gmail API
- Reading emails and threads
- Sending emails
- Managing labels
- Gmail inbox data access via clean API
- Laravel service container integration

## Development Commands

### Installation

```bash
composer install
```

### Testing

The project uses PestPHP for testing:

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run a specific test file
./vendor/bin/pest tests/path/to/TestFile.php
```

### Static Analysis

PHPStan is used for static analysis with level 5:

```bash
# Run PHPStan analysis
composer analyse
```

### Code Formatting

Laravel Pint is used for code formatting:

```bash
# Format code
composer format
```

### Development Environment

For local development with Laravel's testbench:

```bash
# Build the workbench environment
composer build

# Start the test server
composer start

# Clear the package
composer clear

# Prepare package discovery
composer prepare
```

## Architecture Overview

### Core Components

1. **GmailClient** (`src/GmailClient.php`): 
   - Main client class that handles email operations
   - Provides methods for authentication, message operations, and label management
   - Entry point for most package functionality with methods like `authenticate()`, `listMessages()`, `getMessage()`, `sendEmail()`, `listLabels()`

2. **GmailConnector** (`src/Gmail/GmailConnector.php`):
   - Saloon connector for Gmail API
   - Handles API base URL and authentication
   - Configures base request settings for Gmail API interactions

3. **Resources** (`src/Gmail/Resources/`):
   - **AuthResource**: Handles OAuth authentication flows (authorization URLs, token exchange, refresh)
   - **MessageResource**: Message CRUD operations (list, get, send)
   - **LabelResource**: Label management (list, get, create, update, delete)

4. **Data Objects** (`src/Data/`):
   - **Email**: Represents email messages with all metadata (headers, body, attachments)
   - **Label**: Represents Gmail labels with properties like name, type, and visibility settings

5. **Authentication** (`src/Gmail/GmailOAuthAuthenticator.php`):
   - OAuth authentication implementation using Saloon's authenticator interface
   - Token management and refresh functionality

### Request Flow

1. User authenticates via OAuth flow
2. GmailClient uses the obtained token to authenticate with Gmail API
3. Resource classes make specific API requests through the connector
4. Response data is transformed into structured Data objects
5. Errors are caught and transformed into specific exception types

### Exception Handling

Custom exceptions in `src/Exceptions/` for different error scenarios:
- **AuthenticationException**: OAuth and token-related errors
- **GmailClientException**: General client errors
- **NotFoundException**: Resource not found errors (e.g., emails, labels)
- **RateLimitException**: API quota exceeded errors with retry-after information
- **ValidationException**: Data validation errors

### Service Provider

`GmailClientServiceProvider` registers the package with Laravel and handles:
- Configuration publishing
- Route registration (optional)
- Facade binding

## Testing Strategy

- Tests are written using PestPHP
- TestCase extends Orchestra Testbench for Laravel integration testing
- Mocking of API responses using Saloon's mock capabilities
- Test fixtures in tests/fixtures/ directory

## Current Development Status

### Gmail Label Management (Added in v1.0.4)

**Feature**: Complete Gmail label management functionality for email organization.

**Implementation**:
- **`addLabelsToMessage()`** - Add labels to messages (starring, marking important)
- **`removeLabelsFromMessage()`** - Remove labels (mark read, archive)
- **`modifyMessageLabels()`** - Add and remove labels in single API call
- **`ModifyMessageLabelsRequest`** - Proper Gmail API integration using messages.modify endpoint

**Common Use Cases**:
```php
// ⭐ Star a message
$client->addLabelsToMessage($messageId, ['STARRED']);

// 📥 Mark as read
$client->removeLabelsFromMessage($messageId, ['UNREAD']);

// 📂 Archive message  
$client->removeLabelsFromMessage($messageId, ['INBOX']);

// ⚡ Efficient batch operations
$client->modifyMessageLabels($messageId, ['STARRED'], ['UNREAD']);
```

**GitHub Issue**: [#16](https://github.com/PartridgeRocks/laravel-gmail-client/issues/16) - Completed ✅

### Null-Safe Methods for Robust Applications (Added in v1.0.5)

**Feature**: Comprehensive null-safe method suite for graceful degradation in production applications.

**Implementation**:
- **`safeListLabels()`** - Returns empty collection on failure instead of throwing exceptions
- **`safeListMessages()`** - Returns empty collection on errors (auth, rate limits, etc.)
- **`safeGetMessage()`** - Returns null when message not found or API fails
- **`safeGetAccountStatistics()`** - Returns fallback data with error indicators when API unavailable
- **`isConnected()`** - Simple health check for Gmail API connection status
- **`getAccountSummary()`** - Safe overview of account status, labels count, and message statistics
- **Common `safeCall()` helper** - DRY error handling with consistent logging and fallback patterns

**Common Use Cases**:
```php
// ✅ Dashboard widgets - never crash on API failures
$labels = $client->safeListLabels();
$summary = $client->getAccountSummary();

// ✅ Background processing with error tolerance
$messages = $client->safeListMessages(['q' => 'is:unread']);
if ($client->isConnected()) {
    // Safe to proceed with operations
}

// ✅ Health monitoring with graceful degradation
$stats = $client->safeGetAccountStatistics();
if ($stats['partial_failure']) {
    // Handle degraded service gracefully
}
```

**Benefits**:
- **Dashboard reliability** - UI components never crash due to Gmail API failures
- **Background processing** - Sync processes continue with partial data instead of failing
- **Health monitoring** - Connection status checks without exception handling complexity
- **Production stability** - Graceful degradation for user-facing applications

**GitHub Issue**: Response to user request for null-safe `listLabels()` method - Completed ✅

### Service Layer Architecture Enhancement (v1.5.0)

**Feature**: Extracted statistics functionality into dedicated service layer with comprehensive interface contracts.

**Implementation**:
- **`StatisticsServiceInterface`** - Complete contract for account statistics and health monitoring
- **`StatisticsService`** - Full implementation with smart performance optimizations
- **Dependency injection integration** - Proper Laravel service container bindings with singleton pattern
- **Background mode processing** - Safe statistics gathering for dashboard widgets
- **Comprehensive test coverage** - 31 new tests covering all service methods and edge cases

**Key Features**:
```php
// Comprehensive account statistics with smart counting
$stats = $client->getAccountStatistics([
    'unread_limit' => 25,
    'include_labels' => true,
    'estimate_large_counts' => true,
    'background_mode' => false
]);

// Health monitoring for connection status
$health = $client->getAccountHealth();
// Returns: connected, status, api_quota_remaining, errors

// Safe operations for production dashboards
$summary = $client->getAccountSummary();
$safeStats = $client->safeGetAccountStatistics();
$isHealthy = $client->isConnected();
```

**Architecture Benefits**:
- **Clean separation of concerns** - Statistics logic extracted from main client class
- **Testability** - Interface contracts enable easy mocking and testing
- **Performance optimization** - Smart counting, background mode, configurable limits
- **Production reliability** - Safe methods with graceful fallbacks for dashboard widgets
- **Extensibility** - Easy to swap implementations or add new statistics features

**Configuration Integration**:
```php
// Uses existing gmail-client.performance config
'performance' => [
    'enable_smart_counting' => true,
    'count_estimation_threshold' => 50,
    'api_timeout' => 30,
]
```

**Testing Coverage**: 31 new tests covering service contracts, dependency injection, error handling, and configuration integration.

### OAuth Authentication Bug (Fixed in v1.0.2)

**Issue**: Missing `fromOAuthError()` method in `AuthenticationException` causing OAuth failures.

**Details**:
- `AuthResource.php:40` calls `AuthenticationException::fromOAuthError()` but method didn't exist
- Error: `Call to undefined method PartridgeRocks\GmailClient\Exceptions\AuthenticationException::fromOAuthError()`

**Resolution**:
- Added `fromOAuthError(string $message): self` method to `AuthenticationException`
- Added `oauth_error` type to `AuthenticationErrorDTO`
- **GitHub Issue**: [#11](https://github.com/PartridgeRocks/laravel-gmail-client/issues/11)
- **PR Status**: Merged ✅

### Comprehensive OAuth & Exception Testing (v1.0.3)

**Enhancement**: Added extensive test coverage for OAuth authentication and exception handling.

**Implementation**:
- **24 new test cases** covering OAuth authentication flows
- **Exception chaining** throughout all factory methods with `?\Throwable $previous`
- **Type-safe constants** replacing magic strings (`AuthenticationErrorDTO::OAUTH_ERROR`)
- **Enhanced error context** with OAuth flow metadata and timestamps

**Impact**:
- **Test coverage**: Improved from ~45% to ~75%
- **Total tests**: Increased from 25 to 67 passing tests
- **Assertions**: Grew from 77 to 185 total assertions
- **Quality assurance**: All PHPStan Level 5 checks passing

### OAuth Integration Testing

For detailed OAuth testing instructions, see [TESTING.md](TESTING.md).

## Development Workflow & Best Practices

### Git Workflow
**Important**: Always use feature branches with rebase (not merge) for clean history.

```bash
# ✅ Proper workflow with rebase
git checkout -b feature/new-functionality
# ... implement feature ...

# Before creating PR - rebase to latest master
git fetch origin master
git rebase origin/master
git push -u origin feature/new-functionality
gh pr create --title "Add New Functionality" --body "..."

# If conflicts arise after PR created
git fetch origin master  
git rebase origin/master
# Fix conflicts in each commit during rebase
git rebase --continue
git push --force-with-lease origin feature/new-functionality

# ❌ Avoid direct master commits
git push origin master  # Bypasses code review!

# ❌ Avoid merge commits for conflict resolution
git merge origin/master  # Creates merge commit noise in history
```

**Why Rebase > Merge for Conflicts:**
- **Clean linear history** - no unnecessary merge commits
- **Cleaner PR history** - each commit tells a clear story  
- **Easier code review** - reviewers see actual changes, not merge noise
- **Better git log** - `git log --oneline` stays readable
- **Standard practice** - most teams prefer rebasing feature branches

### Testing Strategy
1. **Saloon MockClient**: Use `MockClient` and `MockResponse` for API testing, not Laravel's `Http::fake()`
2. **Complete mock data**: Include all required fields (`sizeEstimate`, `internalDate`, etc.)
3. **Error scenarios**: Test 401, 404, 429 status codes with proper exception assertions
4. **Real-world scenarios**: Test common Gmail operations (star, archive, mark read)

### Architecture Patterns
- **Exception chaining**: Always include `?\Throwable $previous` parameters
- **Type-safe constants**: Use class constants instead of magic strings
- **Consistent error handling**: Follow established patterns across all methods
- **Comprehensive documentation**: Include PHPDoc with `@throws` annotations

### Code Quality Pipeline
```bash
composer test           # 67 tests, 185 assertions
composer analyse        # PHPStan Level 5
composer format         # Laravel Pint
```

### Issue Resolution Process
1. **Identify root cause** - Missing method, inadequate testing, etc.
2. **Plan implementation** - Use TodoWrite to track progress
3. **Implement with tests** - TDD approach with comprehensive coverage
4. **Quality assurance** - All linting and analysis tools must pass
5. **Documentation** - Update CLAUDE.md and issue tracking

### Key Learnings
- **OAuth complexity**: Requires extensive testing for production readiness
- **Saloon integration**: Proper mocking requires understanding of Saloon's architecture
- **Gmail API patterns**: Label management follows specific endpoint conventions
- **Laravel package development**: Balance between Laravel conventions and Gmail API requirements

## Additional Notes

- PHP 8.2 is required
- Compatible with Laravel 10.x and 11.x
- Uses SaloonPHP v3 for API interactions
- Uses Laravel Data v4 for data structures