<?php

namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Commands\LaravelMermaidErdCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMermaidErdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mermaid-erd')
            ->hasConfigFile()
            ->hasCommand(LaravelMermaidErdCommand::class);
    }

    public function bootingPackage(): void
    {
        $this->app->bind(DatabaseInformationService::class, function () {
            return new DatabaseInformationService(
                $this->app->make('db')->connection(),
                config('mermaid-erd.ignore_tables', [])
            );
        });
    }
}
