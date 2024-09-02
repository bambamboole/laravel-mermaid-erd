<?php

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Database\Connection;

beforeEach(function () {
    $this->dbMock = $this->createMock(Connection::class);
    $this->service = new DatabaseInformationService($this->dbMock);
});

it('retrieves tables', function () {
    $this->dbMock->method('getDatabaseName')->willReturn('test_db');
    $this->dbMock->method('select')->with('SHOW TABLES')->willReturn([
        (object) ['Tables_in_test_db' => 'users'],
        (object) ['Tables_in_test_db' => 'posts'],
    ]);

    $tables = $this->service->getTables();
    expect($tables)->toBe(['users', 'posts']);
});

it('retrieves foreign keys', function () {
    $this->dbMock->method('select')->with("SHOW KEYS FROM `users` WHERE Key_name like '%_foreign'")->willReturn([
        (object) ['Column_name' => 'user_id'],
    ]);

    $foreignKeys = $this->service->getForeignKeys('users');
    expect($foreignKeys)->toEqual([(object) ['Column_name' => 'user_id']]);
});

it('retrieves column listings', function () {
    $schemaBuilderMock = $this->createMock(\Illuminate\Database\Schema\Builder::class);
    $this->dbMock->method('getSchemaBuilder')->willReturn($schemaBuilderMock);
    $schemaBuilderMock->method('getColumnListing')->with('users')->willReturn(['id', 'name', 'email']);

    $columns = $this->service->getColumnListing('users');
    expect($columns)->toBe(['id', 'name', 'email']);
});

it('retrieves column types', function () {
    $schemaBuilderMock = $this->createMock(\Illuminate\Database\Schema\Builder::class);
    $this->dbMock->method('getSchemaBuilder')->willReturn($schemaBuilderMock);
    $schemaBuilderMock->method('getColumnType')->with('users', 'id')->willReturn('int');

    $type = $this->service->getColumnType('users', 'id');
    expect($type)->toBe('int');
});
