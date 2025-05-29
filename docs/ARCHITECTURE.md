# Laravel Gmail Client Architecture

This document provides an in-depth explanation of the Laravel Gmail Client architecture, design decisions, and implementation details.

## Overview

Laravel Gmail Client is a package that integrates with the Gmail API to provide a clean, Laravel-friendly interface for managing emails within Laravel applications. The package is built with [Saloon PHP](https://github.com/saloonphp/saloon) for API interactions and [Laravel Data](https://github.com/spatie/laravel-data) for data structures.

## Core Design Principles

The package follows these core design principles:

1. **Laravel-First Approach**: Embraces Laravel conventions and integrates with Laravel's service container, facades, and configuration system.
2. **Clean API**: Provides a simple, intuitive API for Gmail operations while hiding the complexity of the underlying API.
3. **Type Safety**: Uses strongly-typed data objects to represent Gmail entities like messages and labels.
4. **Error Handling**: Provides detailed, specific exceptions for different error scenarios.
5. **Memory Efficiency**: Includes options for memory-efficient processing of large datasets.
6. **Testability**: Designed with testing in mind, with full support for mocking and testing Gmail operations.

## Package Structure

The package is organized into the following main components:

```
src/
├── Builders/             # Builder pattern implementations
│   └── GmailClientBuilder.php
├── Commands/             # Artisan commands
├── Config/               # Type-safe configuration objects
│   ├── GmailConfig.php
│   ├── CacheConfig.php
│   ├── PerformanceConfig.php
│   └── ...
├── Contracts/            # Service interface contracts
│   ├── AuthServiceInterface.php
│   ├── MessageServiceInterface.php
│   ├── LabelServiceInterface.php
│   ├── StatisticsServiceInterface.php
│   └── Composite/        # Composite interfaces
├── Data/                 # Data objects and DTOs
│   ├── Errors/           # Error DTOs for structured error responses
│   └── Responses/        # Response DTOs for API responses
├── Exceptions/           # Custom exceptions
├── Facades/              # Laravel facades
├── Factories/            # Service factory pattern
│   └── GmailServiceFactory.php
├── Gmail/                # Gmail API integration
│   ├── Pagination/       # Pagination utilities
│   ├── Requests/         # Saloon requests for various API endpoints
│   │   ├── Auth/         # Authentication requests
│   │   ├── Labels/       # Label management requests
│   │   └── Messages/     # Message management requests
│   └── Resources/        # Resources grouping related requests
├── Http/                 # HTTP controllers
│   └── Controllers/      # Authentication controllers
├── Repositories/         # Repository pattern implementations
│   ├── MessageRepository.php
│   └── LabelRepository.php
├── Services/             # Service layer implementations
│   ├── AuthService.php
│   ├── MessageService.php
│   ├── LabelService.php
│   └── StatisticsService.php
├── GmailClient.php       # Main client class
├── GmailReadOnlyClient.php # Read-only client variant
└── GmailClientServiceProvider.php  # Laravel service provider
```

## Core Components

### 1. GmailClient

The `GmailClient` class (`src/GmailClient.php`) is the main entry point for the package. It provides methods for:

- Authenticating with the Gmail API
- Managing messages (listing, getting, sending)
- Managing labels (listing, getting, creating, updating, deleting)
- Handling pagination and lazy loading
- Account statistics and health monitoring

The client uses resource classes to group related functionality and provides a clean, high-level API for common operations. With the architectural improvements, the client now supports:

- **Builder Pattern**: Use `GmailClientBuilder` for fluent client construction
- **Service Layer Integration**: Automatic service resolution via dependency injection
- **Type-safe Configuration**: Configuration objects for better type safety
- **Repository Pattern**: Data access abstraction for enhanced testability

### 1.1. GmailClientBuilder

The `GmailClientBuilder` class (`src/Builders/GmailClientBuilder.php`) provides a fluent interface for client construction:

```php
$client = GmailClientBuilder::create()
    ->withToken($accessToken)
    ->withRefreshToken($refreshToken)
    ->withConfig($config)
    ->build();
```

### 1.2. Service Layer Architecture

The package now includes a comprehensive service layer with interface contracts:

- **GmailServiceFactory**: Centralized service creation and management
- **AuthService**: OAuth authentication and token management  
- **MessageService**: Message operations and data access
- **LabelService**: Label management functionality
- **StatisticsService**: Account statistics and health monitoring

All services implement proper interface contracts for enhanced testability and dependency injection.

### 2. GmailConnector

The `GmailConnector` class (`src/Gmail/GmailConnector.php`) is a Saloon connector that handles communication with the Gmail API. It:

- Sets the base URL for the Gmail API
- Configures default headers and query parameters
- Manages authentication
- Handles rate limiting and retries

### 3. Resources

Resources are classes that group related requests to the Gmail API:

- **AuthResource**: Handles OAuth authentication flow (authorization, token exchange, token refresh)
- **MessageResource**: Handles message operations (list, get, send)
- **LabelResource**: Handles label operations (list, get, create, update, delete)

Each resource takes a `GmailConnector` instance for making API requests.

### 4. Configuration Objects

Type-safe configuration objects provide better validation and IDE support:

- **GmailConfig**: Core Gmail API configuration (client ID, secret, scopes)
- **CacheConfig**: Caching settings for performance optimization
- **PerformanceConfig**: Performance-related settings (timeouts, limits)
- **RateLimitConfig**: Rate limiting configuration
- **LoggingConfig**: Logging configuration for debugging

### 5. Service Factory Pattern

The `GmailServiceFactory` centralizes service creation and manages dependencies:

```php
$factory = new GmailServiceFactory($connector, $config);
$services = $factory->createAllServices();

// Access individual services
$authService = $factory->createAuthService();
$messageService = $factory->createMessageService();
$labelService = $factory->createLabelService();
$statisticsService = $factory->createStatisticsService();
```

### 6. Repository Pattern

Repository classes provide data access abstraction:

- **MessageRepository**: Rich query methods for message data access
- **LabelRepository**: Label data access with advanced filtering

```php
$messageRepo = new MessageRepository($connector);
$unreadMessages = $messageRepo->findUnread($maxResults = 25);
$todayMessages = $messageRepo->findByDateRange(today(), now());
```

### 7. Data Objects

Data objects are strongly-typed representations of Gmail entities:

- **Email**: Represents an email message with all its properties (headers, body, attachments)
- **Label**: Represents a Gmail label with its properties (name, type, visibility)

These data objects use Laravel Data for consistent structure and transformations.

### 8. Requests

Requests are Saloon request classes for specific API endpoints:

- **Auth Requests**: Exchange code, refresh token
- **Message Requests**: List messages, get message, send message
- **Label Requests**: List labels, get label, create label, update label, delete label

Each request is responsible for building its own URL, headers, and body.

### 9. Pagination

The package includes two approaches to pagination:

- **GmailPaginator**: A traditional paginator that loads pages on demand
- **GmailLazyCollection**: A lazy collection that loads items on demand for memory-efficient processing

### 10. Exception Handling

Custom exceptions provide detailed error information:

- **AuthenticationException**: OAuth and token-related errors
- **NotFoundException**: Resource not found errors
- **RateLimitException**: API quota exceeded errors
- **ValidationException**: Data validation errors
- **GmailClientException**: General client errors

## Authentication Flow

The package implements the OAuth 2.0 authentication flow for Gmail:

1. **Authorization Request**: Gets an authorization URL for the user to grant permission
2. **Code Exchange**: Exchanges an authorization code for access and refresh tokens
3. **Token Storage**: Stores tokens securely (in the session, database, or elsewhere)
4. **Token Refresh**: Refreshes expired tokens automatically if a refresh token is available

The package provides two ways to implement this flow:

1. **Manual Flow**: Developers handle the complete flow, including redirects and token storage
2. **Built-in Routes**: The package can automatically register authentication routes

## API Request Flow

A typical API request follows this flow:

1. Client method is called
2. Resource method is called
3. Request object is created and sent through the connector
4. Response is processed and transformed into a data object
5. Data object is returned to the caller

For example, when calling `GmailClient::getMessage('message-id')`:

1. `GmailClient::getMessage()` method is called
2. `MessageResource::get()` method is called
3. `GetMessageRequest` is created and sent through the connector
4. Response is transformed into an `Email` data object
5. `Email` object is returned to the caller

## Memory Efficiency

For large Gmail accounts, memory efficiency is crucial. The package provides three approaches:

1. **Standard Loading**: Loads all messages into memory at once (suitable for small datasets)
2. **Pagination**: Loads messages page by page (suitable for medium datasets)
3. **Lazy Loading**: Loads messages one by one as needed (suitable for large datasets)

The lazy loading approach is implemented using Laravel's `LazyCollection` for efficient memory usage.

## Service Provider

The `GmailClientServiceProvider` handles:

- Registering the package with Laravel
- Publishing configuration files
- Registering routes for authentication
- Binding services in the Laravel service container with proper interface contracts
- Setting up the facade
- Configuring dependency injection for all service interfaces

The service provider now registers all service interfaces as singletons:

```php
// Service interface bindings
$this->app->singleton(AuthServiceInterface::class, AuthService::class);
$this->app->singleton(MessageServiceInterface::class, MessageService::class);
$this->app->singleton(LabelServiceInterface::class, LabelService::class);
$this->app->singleton(StatisticsServiceInterface::class, StatisticsService::class);

// Factory binding
$this->app->singleton(GmailServiceFactory::class);
```

## Testing

The package is designed for testability:

- Saloon's built-in mocking capabilities make it easy to test without real API calls
- The package's service container binding makes it easy to mock the client in tests
- The resource/request architecture makes it easy to mock specific API endpoints

## Design Decisions

### Why Saloon?

Saloon was chosen as the HTTP client because:

- It provides a clean, object-oriented approach to API integrations
- It has built-in support for authentication, rate limiting, and retries
- It provides excellent tools for testing and mocking responses
- It follows Laravel's design principles and integrates well with Laravel

### Why Laravel Data?

Laravel Data was chosen for data structures because:

- It provides strongly-typed data objects with validation
- It supports casting and transforming data
- It integrates well with Laravel's validation and serialization systems
- It makes it easy to transform API responses into clean, usable objects

### Why Resource Classes?

Resource classes were chosen to group related functionality because:

- They provide a clean separation of concerns
- They make the code more maintainable and testable
- They follow RESTful principles
- They make it easy to extend the package with new functionality

### Error Handling Approach

The package uses specific exception types for different error scenarios:

- Each exception type provides context-specific information
- Catch blocks can be specific to the error type
- Error messages are clear and actionable
- Rate limit errors include retry-after information

## Future Improvements

Possible future improvements to the architecture:

1. **Event System**: Expand the event system to cover more scenarios
2. **Webhook Support**: Add support for Gmail push notifications via webhooks
3. **Caching Layer**: Add a caching layer for frequently accessed data
4. **Batch Operations**: Add support for batch operations to optimize API usage
5. **Attachments**: Improve support for handling email attachments
6. **Thread Operations**: Add dedicated support for thread operations
7. **Draft Management**: Add support for managing email drafts

## Integrating with Applications

The package is designed to be integrated into Laravel applications in multiple ways:

1. **Facade**: Using the `GmailClient` facade for quick access
2. **Dependency Injection**: Injecting the client into controllers or services
3. **User Integration**: Extending the User model with Gmail connections
4. **Events**: Listening for Gmail events in the application

## Conclusion

The Laravel Gmail Client architecture is designed to provide a clean, maintainable, and memory-efficient way to interact with the Gmail API in Laravel applications. By following Laravel conventions and best practices, it integrates seamlessly with Laravel applications while hiding the complexity of the underlying API.