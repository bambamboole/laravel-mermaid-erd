<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Commands\LaravelMermaidErdCommand;
use Bambamboole\LaravelMermaidErd\Schema\ModelScanner;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
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
        $this->app->bind(DatabaseInformationService::class, fn ($app): DatabaseInformationService => new DatabaseInformationService(
            $app->make('db')->connection(),
            config('mermaid-erd.ignore_tables', []),
            [],
            config('mermaid-erd.schema'),
        ));

        $this->app->bind(ModelScanner::class, fn (): ModelScanner => new ModelScanner(
            config('mermaid-erd.models.paths') ?? [app_path('Models')],
        ));

        $this->app->bind(SchemaBuilder::class, fn ($app): SchemaBuilder => new SchemaBuilder(
            $app->make(DatabaseInformationService::class),
            config('mermaid-erd.polymorphic_relationships', []),
            config('mermaid-erd.guess_relationships', true),
            config('mermaid-erd.models.enabled', true) ? $app->make(ModelScanner::class)->scan() : null,
        ));
    }
}
