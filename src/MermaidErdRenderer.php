<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd;

use Bambamboole\LaravelMermaidErd\Schema\Column;
use Bambamboole\LaravelMermaidErd\Schema\Relation;
use Bambamboole\LaravelMermaidErd\Schema\RelationType;
use Bambamboole\LaravelMermaidErd\Schema\Schema;
use Bambamboole\LaravelMermaidErd\Schema\Table;

class MermaidErdRenderer
{
    public function render(Schema $schema): string
    {
        $tableCount = count($schema->tables);
        $totalColumns = array_sum(array_map(fn (Table $table): int => count($table->columns), $schema->tables));

        $diagram = "---\ntitle: {$tableCount} tables · {$totalColumns} columns\n---\nerDiagram\n";

        foreach ($schema->tables as $table) {
            $diagram .= $this->renderTable($table);
        }

        foreach ($schema->relations as $relation) {
            $diagram .= $this->renderRelation($relation, $schema);
        }

        $unmapped = $schema->unmappedMorphs();
        if ($unmapped !== []) {
            $diagram .= "%% Unmapped polymorphic relations (add to config 'mermaid-erd.polymorphic_relationships'):\n";
            foreach ($unmapped as $pair) {
                [$tableName, $morphName] = explode('.', $pair, 2);
                $noIndex = in_array($morphName, $schema->table($tableName)->unindexedMorphs ?? []) ? ', no index' : '';
                $diagram .= "%%   {$pair} ({$morphName}_type + {$morphName}_id{$noIndex})\n";
            }
        }

        return $diagram;
    }

    private function renderTable(Table $table): string
    {
        $columnCount = count($table->columns);
        $header = "{$table->name} ({$columnCount})";
        if ($table->model !== null) {
            $header .= ' · '.class_basename($table->model);
        }
        $diagram = "    {$table->name}[\"{$header}\"] {\n";

        foreach ($table->columns as $column) {
            $diagram .= $this->renderColumn($column)."\n";
        }

        return $diagram."    }\n";
    }

    private function renderColumn(Column $column): string
    {
        $line = "        {$column->type} {$column->name}";

        $constraints = [];
        if ($column->primaryKey) {
            $constraints[] = 'PK';
        }
        if ($column->foreignKey) {
            $constraints[] = 'FK';
        }
        if ($column->unique && ! $column->primaryKey) {
            $constraints[] = 'UK';
        }
        if ($constraints !== []) {
            $line .= ' '.implode(', ', $constraints);
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
        if ($comments !== []) {
            $line .= ' "'.implode(', ', $comments).'"';
        }

        return $line;
    }

    private function renderRelation(Relation $relation, Schema $schema): string
    {
        $parentSide = $relation->nullable ? '|o' : '||';
        $noIndex = $relation->indexed ? '' : ', no index';

        return match ($relation->type) {
            RelationType::ForeignKey => $this->renderForeignKeyRelation($relation, $schema, $parentSide),
            RelationType::Eloquent => $this->renderEloquentRelation($relation, $schema, $parentSide),
            RelationType::Guessed => "    {$relation->from} {$parentSide}--o{ {$relation->to} : \"guessed has many via {$relation->columns[0]}{$noIndex}\"\n",
            RelationType::Morph => "    {$relation->from} ||--o{ {$relation->to} : \"morphMany via {$relation->morphName}{$noIndex}\"\n",
        };
    }

    private function renderEloquentRelation(Relation $relation, Schema $schema, string $parentSide): string
    {
        $label = "{$relation->declaredAs} via {$relation->columns[0]}";

        $column = $schema->table($relation->to)?->columns[$relation->columns[0]] ?? null;
        if (! $relation->indexed) {
            $label .= ', no index';
        } elseif ($relation->declaredAs === 'hasOne' && $column !== null && ! $column->unique) {
            // The code assumes one child row per parent; the database permits more.
            $label .= ', no unique index';
        }

        return sprintf(
            "    %s %s--%s %s : \"%s\"\n",
            $relation->from,
            $parentSide,
            $relation->oneToOne ? '||' : 'o{',
            $relation->to,
            $label,
        );
    }

    private function renderForeignKeyRelation(Relation $relation, Schema $schema, string $parentSide): string
    {
        $childSide = $relation->oneToOne ? '||' : 'o{';

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
            $label = "pivot, {$label}";
        }

        return "    {$relation->from} {$parentSide}--{$childSide} {$relation->to} : \"{$label}\"\n";
    }
}
