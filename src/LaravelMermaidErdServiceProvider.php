<?php

namespace Bambamboole\LaravelMermaidErd;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bambamboole\LaravelMermaidErd\Commands\LaravelMermaidErdCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_mermaid_erd_table')
            ->hasCommand(LaravelMermaidErdCommand::class);
    }
}
