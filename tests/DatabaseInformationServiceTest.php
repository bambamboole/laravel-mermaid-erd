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

// Integration tests using a real database via orchestra/testbench

it('retrieves tables from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('test_authors', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('test_books', function ($table) {
        $table->id();
        $table->string('title');
        $table->foreignId('test_author_id')->constrained('test_authors');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $tables = $service->getTables();

    expect($tables)->toContain('test_authors')
        ->toContain('test_books');

    $schema->dropIfExists('test_books');
    $schema->dropIfExists('test_authors');
});

it('retrieves foreign keys from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('test_departments', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('test_employees', function ($table) {
        $table->id();
        $table->string('name');
        $table->foreignId('test_department_id')->constrained('test_departments');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $foreignKeys = $service->getForeignKeys('test_employees');

    expect($foreignKeys)->toHaveCount(1);
    expect($foreignKeys[0]['foreign_table'])->toBe('test_departments');
    expect($foreignKeys[0]['columns'])->toBe(['test_department_id']);
    expect($foreignKeys[0]['foreign_columns'])->toBe(['id']);

    $schema->dropIfExists('test_employees');
    $schema->dropIfExists('test_departments');
});

it('retrieves column listing from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('test_products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
        $table->text('description')->nullable();
    });

    $service = new DatabaseInformationService($this->app['db']->connection());
    $columns = $service->getColumnListing('test_products');

    expect($columns)->toBe(['id', 'name', 'price', 'description']);

    $schema->dropIfExists('test_products');
});

it('retrieves column types from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('test_items', function ($table) {
        $table->id();
        $table->string('name');
        $table->integer('quantity');
    });

    $service = new DatabaseInformationService($this->app['db']->connection());

    // id is bigint/int8 on MySQL/PG (via $table->id()), integer on SQLite
    expect($service->getColumnType('test_items', 'id'))->toBeIn(['integer', 'bigint', 'int8']);
    expect($service->getColumnType('test_items', 'name'))->toBeIn(['string', 'varchar', 'character varying']);
    expect($service->getColumnType('test_items', 'quantity'))->toBeIn(['integer', 'int', 'int4']);

    $schema->dropIfExists('test_items');
});

it('filters ignored tables from a real database', function () {
    $schema = $this->app['db']->connection()->getSchemaBuilder();
    $schema->create('test_visible', function ($table) {
        $table->id();
        $table->string('name');
    });
    $schema->create('test_ignored_a', function ($table) {
        $table->id();
        $table->string('value');
    });
    $schema->create('test_ignored_b', function ($table) {
        $table->string('id')->primary();
        $table->text('payload');
    });

    $service = new DatabaseInformationService(
        $this->app['db']->connection(),
        ['test_ignored_a', 'test_ignored_b']
    );
    $tables = $service->getTables();

    expect($tables)->toContain('test_visible')
        ->not->toContain('test_ignored_a')
        ->not->toContain('test_ignored_b');

    $schema->dropIfExists('test_ignored_b');
    $schema->dropIfExists('test_ignored_a');
    $schema->dropIfExists('test_visible');
});
