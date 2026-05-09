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
php artisan generate:mermaid-erd --output=file --path=README.md
```

Writes the diagram to the specified file (defaults to `README.md`). The file output uses `<!-- mermaid-erd-start -->` / `<!-- mermaid-erd-end -->` comment tags:

- **File has tags**: replaces content between the tags
- **File exists without tags**: appends an `## ERD` section with the diagram
- **File doesn't exist**: creates it with the diagram

Add these tags where you want the diagram to appear:

```markdown
<!-- mermaid-erd-start -->
<!-- mermaid-erd-end -->
```

### Options

#### `--connection`

Use a specific database connection instead of the default:

```bash
php artisan generate:mermaid-erd --output=stdout --connection=mysql
```

#### `--tables`

Only include specific tables (comma-separated):

```bash
php artisan generate:mermaid-erd --output=stdout --tables=users,posts,comments
```

### Configuration

The config file allows you to ignore specific tables:

```php
return [
    'schema' => null,

    'ignore_tables' => [
        'migrations',
        'failed_jobs',
        'sessions',
        // ...
    ],
];
```

By default, MySQL connections are scoped to the active database name so tables
from other visible databases are not included in the diagram. Set `schema` if you
want to inspect a specific schema/database explicitly.

### Smart relationship detection

The generator automatically detects pivot tables (tables with exactly 2 foreign keys and only `id`/timestamp columns) and renders them as many-to-many relationships instead of separate entities. Foreign key columns with unique indexes are rendered as one-to-one relationships.

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
