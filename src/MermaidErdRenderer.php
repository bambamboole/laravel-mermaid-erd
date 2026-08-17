<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Mermaid\Cardinality;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdAttribute;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdDocument;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdEntity;
use Bambamboole\LaravelMermaidErd\Mermaid\ErdRelation;
use Bambamboole\LaravelMermaidErd\Schema\Column;
use Bambamboole\LaravelMermaidErd\Schema\Relation;
use Bambamboole\LaravelMermaidErd\Schema\RelationType;
use Bambamboole\LaravelMermaidErd\Schema\Schema;
use Bambamboole\LaravelMermaidErd\Schema\Table;

class MermaidErdRenderer
{
    public function render(Schema $schema): string
    {
        return $this->toDocument($schema)->render();
    }

    private function toDocument(Schema $schema): ErdDocument
    {
        $tableCount = count($schema->tables);
        $totalColumns = array_sum(array_map(fn (Table $table): int => count($table->columns), $schema->tables));

        return new ErdDocument(
            title: "{$tableCount} tables · {$totalColumns} columns",
            entities: array_map($this->toEntity(...), array_values($schema->tables)),
            relations: array_map(fn (Relation $relation): ErdRelation => $this->toRelation($relation, $schema), $schema->relations),
            trailingComments: $this->unmappedMorphComments($schema),
        );
    }

    private function toEntity(Table $table): ErdEntity
    {
        $columnCount = count($table->columns);
        $label = "{$table->name} ({$columnCount})";
        if ($table->model !== null) {
            $label .= ' · '.class_basename($table->model);
        }

        return new ErdEntity(
            id: $table->name,
            label: $label,
            attributes: array_map($this->toAttribute(...), array_values($table->columns)),
        );
    }

    private function toAttribute(Column $column): ErdAttribute
    {
        $keys = [];
        if ($column->primaryKey) {
            $keys[] = 'PK';
        }
        if ($column->foreignKey) {
            $keys[] = 'FK';
        }
        if ($column->unique && ! $column->primaryKey) {
            $keys[] = 'UK';
        }

        $comments = [];
        if ($column->softDelete) {
            $comments[] = 'soft-delete';
        }
        if ($column->polymorphic) {
            $comments[] = 'polymorphic';
        }
        if ($column->cast !== null) {
            $cast = str_contains($column->cast, '\\') ? class_basename($column->cast) : $column->cast;
            $comments[] = "cast: {$cast}";
        }
        if ($column->accessor) {
            $comments[] = 'accessor';
        }
        if ($column->mutator) {
            $comments[] = 'mutator';
        }
        if ($column->nullable) {
            $comments[] = 'nullable';
        }
        if ($column->default !== null) {
            $comments[] = "default: {$column->default}";
        }

        return new ErdAttribute(
            name: $column->name,
            type: $column->type,
            keys: $keys,
            comment: $comments === [] ? null : implode(', ', $comments),
        );
    }

    private function toRelation(Relation $relation, Schema $schema): ErdRelation
    {
        $parent = $relation->nullable ? Cardinality::ZeroOrOne : Cardinality::ExactlyOne;
        $noIndex = $relation->indexed ? '' : ', no index';

        return match ($relation->type) {
            RelationType::ForeignKey => new ErdRelation(
                from: $relation->from,
                to: $relation->to,
                parent: $parent,
                child: $relation->oneToOne ? Cardinality::ExactlyOne : Cardinality::ZeroOrMore,
                label: $this->foreignKeyLabel($relation, $schema),
            ),
            RelationType::Eloquent => new ErdRelation(
                from: $relation->from,
                to: $relation->to,
                parent: $parent,
                child: $relation->oneToOne ? Cardinality::ExactlyOne : Cardinality::ZeroOrMore,
                label: $this->eloquentLabel($relation, $schema),
            ),
            RelationType::Guessed => new ErdRelation(
                from: $relation->from,
                to: $relation->to,
                parent: $parent,
                child: Cardinality::ZeroOrMore,
                label: "guessed has many via {$relation->columns[0]}{$noIndex}",
            ),
            RelationType::Morph => new ErdRelation(
                from: $relation->from,
                to: $relation->to,
                parent: Cardinality::ExactlyOne,
                child: Cardinality::ZeroOrMore,
                label: "morphMany via {$relation->morphName}{$noIndex}",
            ),
        };
    }

    private function eloquentLabel(Relation $relation, Schema $schema): string
    {
        $label = "{$relation->declaredAs} via {$relation->columns[0]}";

        $column = $schema->table($relation->to)?->columns[$relation->columns[0]] ?? null;
        if (! $relation->indexed) {
            $label .= ', no index';
        } elseif ($relation->declaredAs === 'hasOne' && $column !== null && ! $column->unique) {
            // The code assumes one child row per parent; the database permits more.
            $label .= ', no unique index';
        }

        return $label;
    }

    private function foreignKeyLabel(Relation $relation, Schema $schema): string
    {
        if ($relation->selfReferential()) {
            $label = 'self-ref';
        } else {
            $label = $relation->oneToOne ? 'has one' : 'has many';
        }
        $label .= ' via '.implode(', ', $relation->columns);

        if ($relation->onDelete !== null) {
            $label .= ", {$relation->onDelete} delete";
        }

        if (! $relation->indexed) {
            $label .= ', no index';
        }

        if ($schema->table($relation->to)?->pivot) {
            return "pivot, {$label}";
        }

        return $label;
    }

    /**
     * @return string[]
     */
    private function unmappedMorphComments(Schema $schema): array
    {
        $unmapped = $schema->unmappedMorphs();
        if ($unmapped === []) {
            return [];
        }

        $comments = ["Unmapped polymorphic relations (add to config 'mermaid-erd.polymorphic_relationships'):"];
        foreach ($unmapped as $pair) {
            [$tableName, $morphName] = explode('.', $pair, 2);
            $noIndex = in_array($morphName, $schema->table($tableName)->unindexedMorphs ?? []) ? ', no index' : '';
            $comments[] = "  {$pair} ({$morphName}_type + {$morphName}_id{$noIndex})";
        }

        return $comments;
    }
}
