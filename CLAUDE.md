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

## Additional Notes

- PHP 8.2 is required
- Compatible with Laravel 10.x and 11.x
- Uses SaloonPHP v3 for API interactions
- Uses Laravel Data v4 for data structures