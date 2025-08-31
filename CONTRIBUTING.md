# Contribution Guide

Thank you for considering contributing to the Ludelix Framework! The Ludelix Framework is built by people just like you, and we appreciate your help in making it better.

## Code of Conduct

Before contributing, please read our [Code of Conduct](CODE_OF_CONDUCT.md). We expect all contributors to abide by its guidelines to maintain a welcoming and inclusive community.

## Ways to Contribute

There are many ways to contribute to Ludelix:

- Reporting bugs
- Suggesting new features
- Writing documentation
- Fixing bugs
- Implementing new features
- Improving existing code
- Reviewing pull requests

## Reporting Bugs

Before submitting a bug report, please check if the bug has already been reported. If you find an existing issue, please add a comment to it instead of creating a new one.

When submitting a bug report, please include:

- A clear and descriptive title
- A detailed description of the issue
- Steps to reproduce the bug
- Expected behavior vs. actual behavior
- Information about your environment (PHP version, OS, etc.)

## Suggesting Features

Feature requests are welcome! Before submitting a feature request, please check if a similar request already exists. 

When submitting a feature request, please include:

- A clear and descriptive title
- A detailed explanation of the feature
- The problem it solves
- Examples of how it would be used
- Any potential drawbacks or considerations

## Pull Request Process

1. Fork the repository
2. Create a new branch for your feature or bug fix
3. Make your changes
4. Add tests for your changes
5. Ensure all tests pass
6. Update documentation as needed
7. Submit your pull request

### Code Style

- Follow PSR-12 coding standards
- Use type declarations wherever possible
- Write clear, self-documenting code
- Add comments only when necessary to clarify complex logic

### Testing

- All pull requests must include relevant tests
- Run all tests before submitting your PR:
  ```bash
  composer run quality
  ```
- Tests should cover both normal and edge cases

### Documentation

- Update the README.md if your changes affect user-facing functionality
- Document new APIs in code comments
- Keep documentation up-to-date with code changes

## Development Environment

To set up your development environment:

1. Clone your fork of the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Run tests to ensure everything is working:
   ```bash
   composer test
   ```

## Questions?

If you have any questions about contributing, feel free to create an issue asking for clarification. We're here to help!

Thank you for contributing to the Ludelix Framework!