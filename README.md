# This is my package laravel-mermaid-erd

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-mermaid-erd.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-mermaid-erd)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/laravel-mermaid-erd/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bambamboole/laravel-mermaid-erd/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bambamboole/laravel-mermaid-erd/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bambamboole/laravel-mermaid-erd/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-mermaid-erd.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-mermaid-erd)

laravel-mermaid-erd is a Laravel package that generates Entity-Relationship Diagrams (ERDs) using Mermaid.js from your database schema. It provides an easy-to-use command to visualize your database structure. This package is ideal for developers who need to document or understand their database relationships quickly.

## Installation

You can install the package via composer:

```bash
composer require bambamboole/laravel-mermaid-erd
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-mermaid-erd-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-mermaid-erd-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-mermaid-erd-views"
```

## Usage

```php
$laravelMermaidErd = new Bambamboole\LaravelMermaidErd();
echo $laravelMermaidErd->echoPhrase('Hello, Bambamboole!');
```

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
