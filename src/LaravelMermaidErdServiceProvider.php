<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Commands\LaravelMermaidErdCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMermaidErdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mermaid-erd')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasCommand(LaravelMermaidErdCommand::class);
    }

    public function bootingPackage(): void
    {
        $this->app->bind(DatabaseInformationService::class, function ($app) {
            return new DatabaseInformationService(
                $app->make('db')->connection(),
                config('mermaid-erd.ignore_tables', []),
                [],
                config('mermaid-erd.schema'),
            );
        });

        $this->app->bind(MermaidErdGenerator::class, function ($app) {
            return new MermaidErdGenerator(
                $app->make(DatabaseInformationService::class),
                config('mermaid-erd.polymorphic_relationships', []),
                config('mermaid-erd.guess_relationships', true),
            );
        });
    }
}
