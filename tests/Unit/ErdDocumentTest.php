<?php

declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\Mermaid\Cardinality;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdAttribute;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdDocument;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdEntity;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdRelation;

it('renders a complete er diagram document', function () {
    $document = new ErdDocument(
        title: '2 tables · 3 columns',
        entities: [
            new ErdEntity('users', 'users (2) · User', [
                new ErdAttribute('id', 'integer', ['PK']),
                new ErdAttribute('email', 'varchar', ['UK'], 'nullable'),
            ]),
            new ErdEntity('posts', 'posts (1)', [
                new ErdAttribute('user_id', 'integer', ['FK']),
            ]),
        ],
        relations: [
            new ErdRelation('users', 'posts', Cardinality::ExactlyOne, Cardinality::ZeroOrMore, 'has many via user_id'),
        ],
        trailingComments: ['a trailing note'],
    );

    expect($document->render())->toBe(<<<'MERMAID'
        ---
        title: 2 tables · 3 columns
        ---
        erDiagram
            users["users (2) · User"] {
                integer id PK
                varchar email UK "nullable"
            }
            posts["posts (1)"] {
                integer user_id FK
            }
            users ||--o{ posts : "has many via user_id"
        %% a trailing note

        MERMAID);
});

it('renders an entity without attributes as an empty block', function () {
    expect(new ErdEntity('logs', 'logs (0)')->render())
        ->toBe("    logs[\"logs (0)\"] {\n    }\n");
});

it('maps cardinalities to side-specific glyphs', function (Cardinality $cardinality, string $left, string $right) {
    expect($cardinality->left())->toBe($left)
        ->and($cardinality->right())->toBe($right);
})->with([
    'zero or one' => [Cardinality::ZeroOrOne, '|o', 'o|'],
    'exactly one' => [Cardinality::ExactlyOne, '||', '||'],
    'zero or more' => [Cardinality::ZeroOrMore, '}o', 'o{'],
]);
