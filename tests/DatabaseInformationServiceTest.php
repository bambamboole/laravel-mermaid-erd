<?php declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;

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

it('scopes mysql table discovery to the current database by default', function () {
    $schemaBuilder = Mockery::mock(Builder::class);
    $schemaBuilder
        ->shouldReceive('getTables')
        ->once()
        ->with('app_database')
        ->andReturn([
            ['name' => 'users'],
            ['name' => 'posts'],
        ]);

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schemaBuilder);
    $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
    $connection->shouldReceive('getDatabaseName')->once()->andReturn('app_database');

    $service = new DatabaseInformationService($connection);

    expect($service->getTables())->toBe(['users', 'posts']);
});

it('uses configured schema for table discovery when provided', function () {
    $schemaBuilder = Mockery::mock(Builder::class);
    $schemaBuilder
        ->shouldReceive('getTables')
        ->once()
        ->with('reporting_schema')
        ->andReturn([
            ['name' => 'invoices'],
        ]);

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schemaBuilder);
    $connection->shouldNotReceive('getDriverName');
    $connection->shouldNotReceive('getDatabaseName');

    $service = new DatabaseInformationService($connection, [], [], 'reporting_schema');

    expect($service->getTables())->toBe(['invoices']);
});

it('passes configured schema to the container-resolved service', function () {
    config()->set('mermaid-erd.schema', 'reporting_schema');

    $service = $this->app->make(DatabaseInformationService::class);

    $schema = (new ReflectionProperty($service, 'schema'))->getValue($service);
    expect($schema)->toBe('reporting_schema');
});

it('keeps default schema discovery for non-mysql connections', function () {
    $schemaBuilder = Mockery::mock(Builder::class);
    $schemaBuilder
        ->shouldReceive('getTables')
        ->once()
        ->with(null)
        ->andReturn([
            ['name' => 'comments'],
        ]);

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schemaBuilder);
    $connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');
    $connection->shouldNotReceive('getDatabaseName');

    $service = new DatabaseInformationService($connection);

    expect($service->getTables())->toBe(['comments']);
});
