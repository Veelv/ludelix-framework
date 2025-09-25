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

### Added
- GitHub community files (CONTRIBUTING.md, CODE_OF_CONDUCT.md, issue/PR templates)
- Automated testing workflow with GitHub Actions
- Enhanced documentation in README.md

### Changed
- Improved project structure for better maintainability

## [1.0.1] - 2025-08-31

### fix: PHP 8.1 compatibility and translate docs

- Downgrade PHPUnit to 9.6.x for PHP 8.1 support
- Fix Logger imports and missing methods
- Resolve test failures
- Translate COMPATIBILITY.md to English

## [1.1.0] - 2025-01-21

### Added
- **Mobile-First Responsive Design**: Implemented mobile-first approach for responsive layouts, enhancing mobile device experience
- **Enhanced Flexbox System**: Improved Flexbox capabilities with better responsive controls and utility classes
- **Advanced CSS Grid System**: Complete CSS Grid system for creating complex and responsive layouts with ease
- **Enhanced JIT Mode**: Improved Just-In-Time compilation mode specifically designed for Fluid framework syntax with `fl-` prefix
- **Optimized Scanner**: Enhanced scanner to detect Fluid-specific syntax patterns
- **Dark Mode Plugin**: Adapted dark mode plugin to work with Fluid-specific color tokens
- **Custom Token Configuration**: Support for custom tokens and personalized configurations
- **Arbitrary Value Support**: Extended support for arbitrary values maintaining Fluid-specific syntax for layout properties

### Enhanced
- **Ludou Template Engine**: Significant improvements in `foreach` directive communication and interaction
- **Fluid-Ludou Integration**: Better integration between Fluid framework and Ludou template engine
- **Responsive System**: Enhanced responsive design capabilities with mobile-first approach
- **Template Compilation**: Improved template compilation process with better error handling and validation

### Improved
- **Developer Experience**: Better syntax highlighting and validation for Fluid framework
- **Performance**: Optimized compilation and rendering processes
- **Cross-Device Compatibility**: Enhanced support for various screen sizes and devices