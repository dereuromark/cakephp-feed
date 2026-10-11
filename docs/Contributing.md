# Contributing

Create a branch for your change and add regression coverage for fixes. Use the repository's Composer commands:

```bash
composer install
composer test
composer cs-check
composer stan-setup
composer stan
```

`stan-setup` installs PHPStan locally while preserving `composer.json`. Static analysis checks both source and tests. The coding standard is PSR-2-R, configured by the repository's PHPCS file.

For website changes, run `npm ci`, `npm run docs:build`, and `npm audit` in `docs/`. Keep examples consistent with the source and check the rendered pages before opening a pull request. CI validates the PHP version matrix, coding standards, static analysis, and documentation.
