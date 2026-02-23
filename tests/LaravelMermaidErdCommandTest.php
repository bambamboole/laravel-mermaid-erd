<?php

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Support\Facades\Artisan;

it('runs the generate:mermaid-erd command', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('cmd_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });
    $schema->create('cmd_posts', function ($table) {
        $table->id();
        $table->foreignId('cmd_user_id')->constrained('cmd_users');
        $table->string('title');
    });

    $this->app->bind(DatabaseInformationService::class, function () {
        return new DatabaseInformationService($this->app['db']->connection());
    });

    Artisan::call('generate:mermaid-erd');
    $output = Artisan::output();

    expect($output)->toContain('erDiagram')
        ->toContain('cmd_users {')
        ->toContain('cmd_posts {');

    $schema->dropIfExists('cmd_posts');
    $schema->dropIfExists('cmd_users');
});

it('outputs valid mermaid erDiagram syntax', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('cmd_categories', function ($table) {
        $table->id();
        $table->string('name');
    });

    $this->app->bind(DatabaseInformationService::class, function () {
        return new DatabaseInformationService($this->app['db']->connection());
    });

    Artisan::call('generate:mermaid-erd');
    $output = Artisan::output();

    expect($output)->toContain('erDiagram')
        ->toContain('cmd_categories {');

    $schema->dropIfExists('cmd_categories');
});
