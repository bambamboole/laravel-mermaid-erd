<?php

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;

it('retrieves tables from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());
    $tables = $service->getTables();

    expect($tables)
        ->toContain('users')
        ->toContain('posts')
        ->toContain('comments')
        ->toContain('tags')
        ->toContain('post_tag');
});

it('retrieves foreign keys from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());
    $foreignKeys = $service->getForeignKeys('comments');

    expect($foreignKeys)->toHaveCount(2);

    $foreignTables = array_column($foreignKeys, 'foreign_table');
    expect($foreignTables)->toContain('posts')->toContain('users');
});

it('retrieves column listing from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());
    $columns = $service->getColumnListing('users');

    expect($columns)->toBe(['id', 'name', 'email', 'created_at', 'updated_at']);
});

it('retrieves column types from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());

    expect($service->getColumnType('users', 'id'))->toBeIn(['integer', 'bigint', 'int8']);
    expect($service->getColumnType('users', 'name'))->toBeIn(['string', 'varchar', 'character varying']);
});

it('filters ignored tables from a real database', function () {
    $service = new DatabaseInformationService(
        $this->app['db']->connection(),
        ['posts', 'comments']
    );
    $tables = $service->getTables();

    expect($tables)
        ->toContain('users')
        ->toContain('tags')
        ->not->toContain('posts')
        ->not->toContain('comments');
});
