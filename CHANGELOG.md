# Changelog

All notable changes to `oi-laravel-development` will be documented in this file.

## [Unreleased]

### Added
- Initial release
- `dev:clear-log` command to clear Laravel log files
- `dev:clear-storage` command to clear storage directories with configurable exceptions
- `dev:force-password` command to quickly reset user passwords during development
- `dev:reset` command for complete database reset with cache clearing
- `init:all` command for interactive seeder selection and execution
- Comprehensive test suite with Pest
- Configuration file for customizing package behavior
- Support for Laravel 11 and 12
- Support for PHP 8.2, 8.3, and 8.4

### Fixed
- Fixed namespace issues in ServiceProvider for proper Laravel 12 compatibility
- Fixed composer.json provider registration path
- Updated publish tags to use array syntax compatible with Laravel 12
- Updated documentation to reflect correct publish tag usage

## [1.0.0] - 2025-11-07

Initial release
