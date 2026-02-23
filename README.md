# Laravel Mermaid ERD

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-mermaid-erd.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-mermaid-erd)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/laravel-mermaid-erd/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bambamboole/laravel-mermaid-erd/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/laravel-mermaid-erd/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bambamboole/laravel-mermaid-erd/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-mermaid-erd.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-mermaid-erd)

Generate Entity-Relationship Diagrams (ERDs) from your Laravel database schema using [Mermaid.js](https://mermaid.js.org/). Visualize tables, columns, and foreign key relationships with a single Artisan command.

## Installation

```bash
composer require bambamboole/laravel-mermaid-erd
```

Optionally publish the config file to customize which tables are ignored:

```bash
php artisan vendor:publish --tag="laravel-mermaid-erd-config"
```

## Usage

```bash
php artisan generate:mermaid-erd
```

When run without options, the command will interactively ask how you want to output the diagram.

### Output modes

#### Print to stdout

```bash
php artisan generate:mermaid-erd --output=stdout
```

Prints the Mermaid ERD diagram directly to the console.

#### Write to a file

```bash
php artisan generate:mermaid-erd --output=file --path=docs/erd.md
```

Writes the diagram wrapped in a mermaid code block to the specified file.

#### Inject into your README

```bash
php artisan generate:mermaid-erd --output=readme --path=README.md
```

Replaces the content between special comment tags in your README with the generated diagram. Add these tags where you want the diagram to appear:

```markdown
<!-- mermaid-erd-start -->
<!-- mermaid-erd-end -->
```

The command will inject the diagram between these tags, preserving the rest of your README.

### Configuration

The config file allows you to ignore specific tables:

```php
return [
    'ignore_tables' => [
        'migrations',
        'failed_jobs',
        'sessions',
        // ...
    ],
];
```

## ERD

<!-- mermaid-erd-start -->
<!-- mermaid-erd-end -->

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [bambamboole](https://github.com/bambamboole)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
