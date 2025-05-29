# Changelog

All notable changes to `gmail_client` will be documented in this file.

## Unreleased

## v2.0.0 - 2025-05-29

### Major - Enterprise Architecture Improvements 🏗️

This release introduces comprehensive architectural enhancements that transform the Gmail Client into an enterprise-grade package with modern design patterns and enhanced testability.

#### 🏗️ Service Layer Architecture
- **Service Factory Pattern**: Centralized service creation with `GmailServiceFactory`
- **Interface Contracts**: Complete interface definitions for all service layers (`AuthServiceInterface`, `MessageServiceInterface`, `LabelServiceInterface`, `StatisticsServiceInterface`)
- **Dependency Injection**: Proper Laravel container integration with singleton bindings
- **Statistics Service**: Dedicated service for account statistics and health monitoring

#### 🔧 Configuration Management
- **Type-safe Config Objects**: `GmailConfig`, `CacheConfig`, `PerformanceConfig`, `RateLimitConfig`, `LoggingConfig`
- **Centralized Configuration**: Single source of truth for all package settings
- **Validation**: Comprehensive config validation with clear error messages

#### 🧪 Enhanced Testing Infrastructure
- **Test Data Builders**: Fluent API for creating test data (`EmailBuilder`, `LabelBuilder`)
- **Mock Factory**: Centralized mock creation with realistic Gmail API responses (`GmailMockFactory`)
- **Contract Testing**: Interface compliance tests for all service contracts
- **Integration Tests**: Comprehensive service integration testing

#### 📐 Clean Architecture Patterns
- **Builder Pattern**: `GmailClientBuilder` for fluent client construction
- **Repository Pattern**: Data access abstraction with rich query methods (`MessageRepository`, `LabelRepository`)
- **SOLID Principles**: Improved separation of concerns and dependency inversion

#### 📊 Enhanced Statistics & Monitoring
- **Account Health Monitoring**: Connection status, API quota, error tracking
- **Smart Performance Optimization**: Background mode, estimation thresholds
- **Safe Operations**: Graceful fallbacks for production dashboard widgets

#### 📚 Documentation Overhaul
- **Updated Architecture Guide**: Complete documentation of new patterns
- **Enhanced Usage Examples**: Builder pattern, service injection, repository usage
- **Enterprise Integration**: Advanced configuration and dependency injection examples

#### 🔧 Quality Assurance
- **176 tests passing** (490 assertions)
- **PHPStan Level 5** compliance - Zero errors
- **Laravel Pint** code style compliance
- **100% Interface Coverage** - All services implement contracts

### Breaking Changes
- Service constructor signatures may have changed due to dependency injection improvements
- Configuration structure enhanced with type-safe objects (backward compatible via config file)

### Migration Guide
The package maintains backward compatibility for basic usage. Advanced users leveraging internal service classes should review the new interface contracts and service factory patterns.

### Fixed
- Fixed header method calls to match Saloon v3 API requirements
- Removed obsolete PHPStan ignore rule for header() method

## v1.0.2 - 2025-05-16

### Fixed
- Fixed class-not-found error in production by properly binding the facade string key
- Fixed header method calls to match Saloon v3 API

## v1.0.1 - 2025-05-12

### Fixed
- GmailClient facade registration in service provider
- Updated facade accessor to use string identifier instead of class name
- Added facade test to ensure proper resolution
- Updated documentation with facade usage examples