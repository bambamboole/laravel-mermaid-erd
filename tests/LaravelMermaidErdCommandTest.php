<?php

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Support\Facades\Artisan;

it('runs the generate:mermaid-erd command', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });
    $schema->create('posts', function ($table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->string('title');
    });

    $this->app->bind(DatabaseInformationService::class, function () {
        return new DatabaseInformationService($this->app['db']->connection());
    });

    Artisan::call('generate:mermaid-erd');
    $output = Artisan::output();

    expect($output)->toContain('erDiagram')
        ->toContain('users {')
        ->toContain('posts {');
});

it('outputs valid mermaid erDiagram syntax', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('categories', function ($table) {
        $table->id();
        $table->string('name');
    });

    $this->app->bind(DatabaseInformationService::class, function () {
        return new DatabaseInformationService($this->app['db']->connection());
    });

    Artisan::call('generate:mermaid-erd');
    $output = Artisan::output();

    expect($output)->toContain('erDiagram')
        ->toContain('categories {');
});
