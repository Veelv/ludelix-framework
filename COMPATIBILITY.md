# Ludelix Framework Compatibility

## Supported PHP Versions

The Ludelix Framework is compatible with the following PHP versions:

- **PHP 8.1+** (minimum recommended version)
- **PHP 8.2** (fully compatible)
- **PHP 8.3** (fully compatible)

## Development Dependencies

### PHPUnit

To ensure compatibility with different PHP versions, we use specific PHPUnit versions:

- **PHP 8.1**: PHPUnit 9.6.x (recommended)
- **PHP 8.2+**: PHPUnit 10.x (optional)

#### Configuration for PHP 8.1

```json
{
  "require-dev": {
    "phpunit/phpunit": "^9.6"
  }
}
```

#### Configuration for PHP 8.2+

```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```

## Installation

### For PHP 8.1

```bash
# Install dependencies
composer install

# Run tests
composer test
```

### For PHP 8.2+

```bash
# Update PHPUnit to version 10.x
composer require --dev phpunit/phpunit:^10.0

# Install dependencies
composer install

# Run tests
composer test
```

## Compatibility Check

To verify if all dependencies are compatible with your PHP version:

```bash
composer check-platform-reqs
```

## Important Notes

1. **PHPUnit 10.x** requires PHP 8.1+ and may have features not available in earlier versions
2. **PHPUnit 9.6.x** is the latest version in the 9.x series that supports PHP 8.1
3. The framework has been tested and works correctly with both PHPUnit versions
4. For production, we recommend using PHP 8.1+ with PHPUnit 9.6.x for maximum compatibility

## Troubleshooting

### PHPUnit Compatibility Error

If you encounter compatibility errors, check:

1. PHP version: `php -v`
2. PHPUnit version: `vendor/bin/phpunit --version`
3. Dependencies: `composer check-platform-reqs`

### Updating PHPUnit

To update PHPUnit:

```bash
# For PHP 8.1
composer require --dev phpunit/phpunit:^9.6

# For PHP 8.2+
composer require --dev phpunit/phpunit:^10.0
```

## Test Structure

The framework includes a complete test suite:

- **Unit Tests**: `tests/Unit/`
- **Integration Tests**: `tests/Integration/`
- **Configuration**: `phpunit.xml`

All tests are compatible with PHPUnit 9.6+ and 10.x.