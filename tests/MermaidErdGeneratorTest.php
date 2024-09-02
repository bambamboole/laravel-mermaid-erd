<?php

declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;

beforeEach(function () {
    $this->databaseInformationServiceMock = $this->createMock(DatabaseInformationService::class);
    $this->generator = new MermaidErdGenerator($this->databaseInformationServiceMock);
});

it('generates a simple ERD', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['users', 'posts']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturnMap([
        ['users', ['id', 'name', 'email']],
        ['posts', ['id', 'user_id', 'title', 'content']],
    ]);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['users', 'email', 'string'],
        ['posts', 'id', 'int'],
        ['posts', 'user_id', 'int'],
        ['posts', 'title', 'string'],
        ['posts', 'content', 'string'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturnMap([
        ['users', []],
        ['posts', [(object) ['Column_name' => 'user_id']]],
    ]);

    $expectedOutput = "erDiagram\n    users {\n        int id\n        string name\n        string email\n    }\n    posts {\n        int id\n        int user_id\n        string title\n        string content\n    }\n    users ||--o{ posts : \"has many via user_id\"\n";
    $output = $this->generator->generate();
    expect($output)->toBe($expectedOutput);
});

it('handles empty input', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn([]);

    $expectedOutput = "erDiagram\n";
    $output = $this->generator->generate();
    expect($output)->toBe($expectedOutput);
});

it('handles invalid input', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['invalid']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willThrowException(new \Exception('Invalid input'));

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid input');
    $this->generator->generate();
});

it('verifies the output format', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['users']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturn(['id', 'name', 'email']);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['users', 'email', 'string'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturn([]);

    $output = $this->generator->generate();
    expect($output)->toMatch('/erDiagram/');
});
