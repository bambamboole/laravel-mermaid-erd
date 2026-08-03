<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Tests;

use Bambamboole\LaravelMermaidErd\LaravelMermaidErdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

use function Orchestra\Testbench\package_path;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelMermaidErdServiceProvider::class];
    }

    /**
     * CI runs the whole suite against MySQL and Postgres as well, so the connection the
     * workbench migrations run on comes from the environment instead of being pinned.
     * Read through getenv() rather than env(): phpunit.xml.dist populates the real
     * process environment, and there is no .env file in a package repository.
     */
    public function getEnvironmentSetUp($app): void
    {
        $connection = $this->envOr('DB_CONNECTION', 'sqlite');

        config([
            'database.default' => $connection,
            "database.connections.{$connection}" => match ($connection) {
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => $this->envOr('DB_HOST', '127.0.0.1'),
                    'port' => $this->envOr('DB_PORT', '3306'),
                    'database' => $this->envOr('DB_DATABASE', 'testing'),
                    'username' => $this->envOr('DB_USERNAME', 'root'),
                    'password' => $this->envOr('DB_PASSWORD', ''),
                ],
                'pgsql' => [
                    'driver' => 'pgsql',
                    'host' => $this->envOr('DB_HOST', '127.0.0.1'),
                    'port' => $this->envOr('DB_PORT', '5432'),
                    'database' => $this->envOr('DB_DATABASE', 'testing'),
                    'username' => $this->envOr('DB_USERNAME', 'postgres'),
                    'password' => $this->envOr('DB_PASSWORD', ''),
                ],
                default => [
                    'driver' => 'sqlite',
                    'database' => $this->envOr('DB_DATABASE', ':memory:'),
                    'prefix' => '',
                ],
            },
        ]);
    }

    private function envOr(string $key, string $default): string
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(package_path('workbench/database/migrations'));
    }
}
