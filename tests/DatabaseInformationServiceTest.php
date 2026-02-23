<?php declare(strict_types=1);

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

it('retrieves columns from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());
    $columns = $service->getColumns('users');

    $names = array_column($columns, 'name');
    expect($names)->toBe(['id', 'name', 'email', 'created_at', 'updated_at']);

    $types = array_column($columns, 'type_name');
    expect($types[0])->toBeIn(['integer', 'bigint', 'int8']);
    expect($types[1])->toBeIn(['varchar', 'character varying']);
});

it('retrieves indexes from a real database', function () {
    $service = new DatabaseInformationService($this->app['db']->connection());
    $indexes = $service->getIndexes('users');

    $primaryIndex = collect($indexes)->first(fn (array $index) => $index['primary']);
    expect($primaryIndex)->not->toBeNull();
    expect($primaryIndex['columns'])->toBe(['id']);
});

it('filters ignored tables from a real database', function () {
    $service = new DatabaseInformationService(
        $this->app['db']->connection(),
        ['posts', 'comments'],
    );
    $tables = $service->getTables();

    expect($tables)
        ->toContain('users')
        ->toContain('tags')
        ->not->toContain('posts')
        ->not->toContain('comments');
});

it('filters to only specified tables', function () {
    $service = new DatabaseInformationService(
        $this->app['db']->connection(),
        [],
        ['users', 'posts'],
    );
    $tables = $service->getTables();

    expect($tables)
        ->toContain('users')
        ->toContain('posts')
        ->not->toContain('comments')
        ->not->toContain('tags')
        ->not->toContain('post_tag');
});
