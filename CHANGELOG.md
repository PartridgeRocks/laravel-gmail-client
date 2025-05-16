# Changelog

All notable changes to `gmail_client` will be documented in this file.

## Unreleased

### Fixed
- Fixed class-not-found error in production by properly binding the facade string key
- Fixed header method calls to match Saloon v3 API

## v1.0.1 - 2025-05-12

### Fixed
- GmailClient facade registration in service provider
- Updated facade accessor to use string identifier instead of class name
- Added facade test to ensure proper resolution
- Updated documentation with facade usage examples
