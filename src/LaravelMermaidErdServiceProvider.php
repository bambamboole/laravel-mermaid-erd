<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Commands\LaravelMermaidErdCommand;
use Bambamboole\LaravelMermaidErd\Schema\ModelScanner;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
use Illuminate\Support\ServiceProvider;

class LaravelMermaidErdServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mermaid-erd.php', 'mermaid-erd');

        $this->app->bind(DatabaseInformationService::class, fn ($app): DatabaseInformationService => new DatabaseInformationService(
            $app->make('db')->connection(),
            config('mermaid-erd.ignore_tables', []),
            [],
            config('mermaid-erd.schema'),
        ));

        $this->app->bind(ModelScanner::class, fn (): ModelScanner => new ModelScanner(
            config('mermaid-erd.models.paths') ?? [app_path()],
        ));

        $this->app->bind(SchemaBuilder::class, fn ($app): SchemaBuilder => SchemaBuilder::fromConfig(
            $app->make(DatabaseInformationService::class),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mermaid-erd');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mermaid-erd.php' => config_path('mermaid-erd.php'),
            ], 'mermaid-erd-config');
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/mermaid-erd'),
            ], 'mermaid-erd-views');
            $this->commands(LaravelMermaidErdCommand::class);
        }
    }
}
