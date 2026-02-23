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
