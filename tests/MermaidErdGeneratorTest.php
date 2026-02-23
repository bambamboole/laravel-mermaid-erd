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
        ['posts', [['name' => 'posts_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']]]],
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

it('handles multiple foreign keys on one table', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['users', 'categories', 'posts']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturnMap([
        ['users', ['id', 'name']],
        ['categories', ['id', 'name']],
        ['posts', ['id', 'user_id', 'category_id', 'title']],
    ]);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['categories', 'id', 'int'],
        ['categories', 'name', 'string'],
        ['posts', 'id', 'int'],
        ['posts', 'user_id', 'int'],
        ['posts', 'category_id', 'int'],
        ['posts', 'title', 'string'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturnMap([
        ['users', []],
        ['categories', []],
        ['posts', [
            ['name' => 'posts_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
            ['name' => 'posts_category_id_foreign', 'columns' => ['category_id'], 'foreign_schema' => null, 'foreign_table' => 'categories', 'foreign_columns' => ['id']],
        ]],
    ]);

    $output = $this->generator->generate();

    expect($output)->toContain('users ||--o{ posts : "has many via user_id"')
        ->toContain('categories ||--o{ posts : "has many via category_id"');
});

it('handles self-referencing foreign key', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['categories']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturn(['id', 'name', 'parent_id']);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['categories', 'id', 'int'],
        ['categories', 'name', 'string'],
        ['categories', 'parent_id', 'int'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturn([
        ['name' => 'categories_parent_id_foreign', 'columns' => ['parent_id'], 'foreign_schema' => null, 'foreign_table' => 'categories', 'foreign_columns' => ['id']],
    ]);

    $output = $this->generator->generate();

    expect($output)->toStartWith('erDiagram')
        ->toContain('categories {')
        ->toContain('int parent_id')
        ->toContain('categories ||--o{ categories : "has many via parent_id"');
});

it('handles composite table names', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['users', 'roles', 'user_roles']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturnMap([
        ['users', ['id', 'name']],
        ['roles', ['id', 'name']],
        ['user_roles', ['id', 'user_id', 'role_id']],
    ]);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['roles', 'id', 'int'],
        ['roles', 'name', 'string'],
        ['user_roles', 'id', 'int'],
        ['user_roles', 'user_id', 'int'],
        ['user_roles', 'role_id', 'int'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturnMap([
        ['users', []],
        ['roles', []],
        ['user_roles', [
            ['name' => 'user_roles_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
            ['name' => 'user_roles_role_id_foreign', 'columns' => ['role_id'], 'foreign_schema' => null, 'foreign_table' => 'roles', 'foreign_columns' => ['id']],
        ]],
    ]);

    $output = $this->generator->generate();

    expect($output)->toContain('user_roles {')
        ->toContain('users ||--o{ user_roles : "has many via user_id"')
        ->toContain('roles ||--o{ user_roles : "has many via role_id"');
});

it('excludes ignored tables from output', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn(['users', 'posts']);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturnMap([
        ['users', ['id', 'name']],
        ['posts', ['id', 'title']],
    ]);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['posts', 'id', 'int'],
        ['posts', 'title', 'string'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturn([]);

    $output = $this->generator->generate();

    expect($output)->toContain('users {')
        ->toContain('posts {')
        ->not->toContain('migrations');
});

it('generates a complex schema with multiple tables and relationships', function () {
    $this->databaseInformationServiceMock->method('getTables')->willReturn([
        'users', 'posts', 'comments', 'tags', 'post_tags',
    ]);
    $this->databaseInformationServiceMock->method('getColumnListing')->willReturnMap([
        ['users', ['id', 'name', 'email']],
        ['posts', ['id', 'user_id', 'title', 'body']],
        ['comments', ['id', 'post_id', 'user_id', 'body']],
        ['tags', ['id', 'name', 'slug']],
        ['post_tags', ['id', 'post_id', 'tag_id']],
    ]);
    $this->databaseInformationServiceMock->method('getColumnType')->willReturnMap([
        ['users', 'id', 'int'],
        ['users', 'name', 'string'],
        ['users', 'email', 'string'],
        ['posts', 'id', 'int'],
        ['posts', 'user_id', 'int'],
        ['posts', 'title', 'string'],
        ['posts', 'body', 'text'],
        ['comments', 'id', 'int'],
        ['comments', 'post_id', 'int'],
        ['comments', 'user_id', 'int'],
        ['comments', 'body', 'text'],
        ['tags', 'id', 'int'],
        ['tags', 'name', 'string'],
        ['tags', 'slug', 'string'],
        ['post_tags', 'id', 'int'],
        ['post_tags', 'post_id', 'int'],
        ['post_tags', 'tag_id', 'int'],
    ]);
    $this->databaseInformationServiceMock->method('getForeignKeys')->willReturnMap([
        ['users', []],
        ['posts', [
            ['name' => 'posts_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
        ]],
        ['comments', [
            ['name' => 'comments_post_id_foreign', 'columns' => ['post_id'], 'foreign_schema' => null, 'foreign_table' => 'posts', 'foreign_columns' => ['id']],
            ['name' => 'comments_user_id_foreign', 'columns' => ['user_id'], 'foreign_schema' => null, 'foreign_table' => 'users', 'foreign_columns' => ['id']],
        ]],
        ['tags', []],
        ['post_tags', [
            ['name' => 'post_tags_post_id_foreign', 'columns' => ['post_id'], 'foreign_schema' => null, 'foreign_table' => 'posts', 'foreign_columns' => ['id']],
            ['name' => 'post_tags_tag_id_foreign', 'columns' => ['tag_id'], 'foreign_schema' => null, 'foreign_table' => 'tags', 'foreign_columns' => ['id']],
        ]],
    ]);

    $output = $this->generator->generate();

    // Verify all tables are present
    expect($output)->toStartWith('erDiagram')
        ->toContain('users {')
        ->toContain('posts {')
        ->toContain('comments {')
        ->toContain('tags {')
        ->toContain('post_tags {');

    // Verify all relationships
    expect($output)->toContain('users ||--o{ posts : "has many via user_id"')
        ->toContain('posts ||--o{ comments : "has many via post_id"')
        ->toContain('users ||--o{ comments : "has many via user_id"')
        ->toContain('posts ||--o{ post_tags : "has many via post_id"')
        ->toContain('tags ||--o{ post_tags : "has many via tag_id"');
});
