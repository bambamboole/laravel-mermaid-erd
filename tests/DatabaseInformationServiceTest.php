<?php

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

beforeEach(function () {
    $this->dbMock = $this->createMock(Connection::class);
    $this->schemaBuilderMock = $this->createMock(SchemaBuilder::class);
    $this->dbMock->method('getSchemaBuilder')->willReturn($this->schemaBuilderMock);
    $this->service = new DatabaseInformationService($this->dbMock);
});

it('retrieves tables', function () {
    $this->schemaBuilderMock->method('getTables')->willReturn([
        ['name' => 'users', 'schema' => null, 'size' => null, 'comment' => null, 'collation' => null, 'engine' => null],
        ['name' => 'posts', 'schema' => null, 'size' => null, 'comment' => null, 'collation' => null, 'engine' => null],
    ]);

    $tables = $this->service->getTables();
    expect($tables)->toBe(['users', 'posts']);
});

it('filters ignored tables', function () {
    $service = new DatabaseInformationService($this->dbMock, ['migrations']);
    $this->schemaBuilderMock->method('getTables')->willReturn([
        ['name' => 'users', 'schema' => null, 'size' => null, 'comment' => null, 'collation' => null, 'engine' => null],
        ['name' => 'migrations', 'schema' => null, 'size' => null, 'comment' => null, 'collation' => null, 'engine' => null],
    ]);

    $tables = $service->getTables();
    expect($tables)->toBe(['users']);
});

it('retrieves foreign keys', function () {
    $this->schemaBuilderMock->method('getForeignKeys')->with('posts')->willReturn([
        ['name' => 'posts_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
    ]);

    $foreignKeys = $this->service->getForeignKeys('posts');
    expect($foreignKeys)->toEqual([
        ['name' => 'posts_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
    ]);
});

it('retrieves column listings', function () {
    $this->schemaBuilderMock->method('getColumnListing')->with('users')->willReturn(['id', 'name', 'email']);

    $columns = $this->service->getColumnListing('users');
    expect($columns)->toBe(['id', 'name', 'email']);
});

it('retrieves column types', function () {
    $this->schemaBuilderMock->method('getColumnType')->with('users', 'id')->willReturn('int');

    $type = $this->service->getColumnType('users', 'id');
    expect($type)->toBe('int');
});

// Integration tests using a real SQLite database via orchestra/testbench

it('retrieves tables from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('authors', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('books', function ($table) {
        $table->id();
        $table->string('title');
        $table->foreignId('author_id')->constrained('authors');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $tables = $service->getTables();

    expect($tables)->toContain('authors')
        ->toContain('books');
});

it('retrieves foreign keys from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('departments', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('employees', function ($table) {
        $table->id();
        $table->string('name');
        $table->foreignId('department_id')->constrained('departments');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $foreignKeys = $service->getForeignKeys('employees');

    expect($foreignKeys)->toHaveCount(1);
    expect($foreignKeys[0]['foreign_table'])->toBe('departments');
    expect($foreignKeys[0]['columns'])->toBe(['department_id']);
    expect($foreignKeys[0]['foreign_columns'])->toBe(['id']);
});

it('retrieves column listing from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
        $table->text('description')->nullable();
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $columns = $service->getColumnListing('products');

    expect($columns)->toBe(['id', 'name', 'price', 'description']);
});

it('retrieves column types from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('items', function ($table) {
        $table->id();
        $table->string('name');
        $table->integer('quantity');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());

    expect($service->getColumnType('items', 'id'))->toBe('integer');
    expect($service->getColumnType('items', 'name'))->toBeIn(['string', 'varchar']);
    expect($service->getColumnType('items', 'quantity'))->toBe('integer');
});

it('filters ignored tables from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('users', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('migrations', function ($table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });
    $schema->create('sessions', function ($table) {
        $table->string('id')->primary();
        $table->text('payload');
    });

    $service = new DatabaseInformationService(
        $this->app['db']->connection(),
        ['migrations', 'sessions']
    );
    $tables = $service->getTables();

    expect($tables)->toContain('users')
        ->not->toContain('migrations')
        ->not->toContain('sessions');
});
