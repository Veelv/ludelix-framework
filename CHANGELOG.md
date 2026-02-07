# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-08-31

### Added
- Core framework with dependency injection container
- Basic routing with fallback response when router is not available
- Template engine (Ludou) with global variables (`title`, `version`, `environment`, `php_version`)
- Mi console with framework and environment information
- Observability and logging via `FileLogger`, with global error, exception, and shutdown handlers
- Multi-tenancy integrations and contracts
- Quality scripts (PHPUnit, PHPStan, Psalm, PHPCS/PHPCBF)

### Changed
- Bootstrap structure and providers for clearer initialization

### Fixed
- Improved robustness in HTTP exception handling and error templates

## [Unreleased]

## [1.0.1] - 2026-02-07

### Added
- Centralized versioning system with `Ludelix\Core\Version` as the single source of truth.
- Independent versioning for Console (`Ludelix\Core\Console\Version`).
- Component-level versioning for Bridge, ApiExplorer, Fluid, GraphQL, Ludou, Routing, Translation, and WebSocket.
- Updated `about` command to display detailed version information for all components.
- GitHub community files (CONTRIBUTING.md, CODE_OF_CONDUCT.md, issue/PR templates)
- Automated testing workflow with GitHub Actions
- Enhanced documentation in README.md

### Changed
- **PHP Compatibility**: Dropped support for PHP 8.1. Now requires PHP >= 8.2.
- Improved project structure for better maintainability

### Fixed
- Resolved dependency conflicts between PHPUnit, PHPStan, and the PHP runtime environment.