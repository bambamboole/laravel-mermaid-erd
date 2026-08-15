<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Schema;

/**
 * @phpstan-type ColumnDetails array{name: string, type: string, pk?: true, fk?: true, uk?: true, nullable?: true, default?: string, softDelete?: true, poly?: true, cast?: string, accessor?: true, mutator?: true}
 * @phpstan-type TableDetails array{columns: ColumnDetails[], pivot?: true, morphs?: string[], unindexedMorphs?: string[]}
 * @phpstan-type RelationDetails array{from: string, to: string, type: 'fk'|'eloquent'|'guessed'|'morph', columns: string[], nullable?: true, oneToOne?: true, onDelete?: string, morphName?: string, declaredAs?: string, unindexed?: true}
 */
readonly class Schema
{
    /**
     * @param  array<string, Table>  $tables  keyed by table name, in discovery order
     * @param  Relation[]  $relations
     */
    public function __construct(
        public array $tables,
        public array $relations,
    ) {}

    public function table(string $name): ?Table
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * Graph representation for the viewer: lightweight lookups for
     * client-side filtering plus structured details for the sidebar.
     *
     * @return array{tables: array<string, string[]>, models: array<string, string>, modelClasses: array<string, string>, pivots: string[], edges: array<int, array{0: string, 1: string}>, details: array<string, TableDetails>, relations: RelationDetails[], unmappedMorphs: string[]}
     */
    public function toGraph(): array
    {
        $tables = [];
        $models = [];
        $modelClasses = [];
        $pivots = [];
        $details = [];
        foreach ($this->tables as $table) {
            $tables[$table->name] = array_keys($table->columns);
            if ($table->model !== null) {
                $models[$table->name] = class_basename($table->model);
                $modelClasses[$table->name] = $table->model;
            }
            if ($table->pivot) {
                $pivots[] = $table->name;
            }
            $details[$table->name] = $this->tableDetails($table);
        }

        $edges = [];
        $seen = [];
        foreach ($this->relations as $relation) {
            $key = "{$relation->from}|{$relation->to}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $edges[] = [$relation->from, $relation->to];
        }

        return [
            'tables' => $tables,
            'models' => $models,
            'modelClasses' => $modelClasses,
            'pivots' => $pivots,
            'edges' => $edges,
            'details' => $details,
            'relations' => array_map($this->relationDetails(...), $this->relations),
            'unmappedMorphs' => $this->unmappedMorphs(),
        ];
    }

    /**
     * @return TableDetails
     */
    private function tableDetails(Table $table): array
    {
        $details = ['columns' => array_map($this->columnDetails(...), array_values($table->columns))];
        if ($table->pivot) {
            $details['pivot'] = true;
        }
        if ($table->morphNames !== []) {
            $details['morphs'] = $table->morphNames;
        }
        if ($table->unindexedMorphs !== []) {
            $details['unindexedMorphs'] = $table->unindexedMorphs;
        }

        return $details;
    }

    /**
     * Falsy fields are omitted to keep the JSON payload small on large schemas.
     *
     * @return ColumnDetails
     */
    private function columnDetails(Column $column): array
    {
        $details = ['name' => $column->name, 'type' => $column->type];
        if ($column->primaryKey) {
            $details['pk'] = true;
        }
        if ($column->foreignKey) {
            $details['fk'] = true;
        }
        if ($column->unique) {
            $details['uk'] = true;
        }
        if ($column->nullable) {
            $details['nullable'] = true;
        }
        if ($column->default !== null) {
            $details['default'] = $column->default;
        }
        if ($column->softDelete) {
            $details['softDelete'] = true;
        }
        if ($column->polymorphic) {
            $details['poly'] = true;
        }
        if ($column->cast !== null) {
            $details['cast'] = $column->cast;
        }
        if ($column->accessor) {
            $details['accessor'] = true;
        }
        if ($column->mutator) {
            $details['mutator'] = true;
        }

        return $details;
    }

    /**
     * @return RelationDetails
     */
    private function relationDetails(Relation $relation): array
    {
        $details = [
            'from' => $relation->from,
            'to' => $relation->to,
            'type' => match ($relation->type) {
                RelationType::ForeignKey => 'fk',
                RelationType::Eloquent => 'eloquent',
                RelationType::Guessed => 'guessed',
                RelationType::Morph => 'morph',
            },
            'columns' => $relation->columns,
        ];
        if ($relation->nullable) {
            $details['nullable'] = true;
        }
        if ($relation->oneToOne) {
            $details['oneToOne'] = true;
        }
        if ($relation->onDelete !== null) {
            $details['onDelete'] = $relation->onDelete;
        }
        if ($relation->morphName !== null) {
            $details['morphName'] = $relation->morphName;
        }
        if ($relation->declaredAs !== null) {
            $details['declaredAs'] = $relation->declaredAs;
        }
        if (! $relation->indexed) {
            $details['unindexed'] = true;
        }

        return $details;
    }

    /**
     * Detected morph pairs without a configured morph relation, as "table.morphName".
     *
     * @return string[]
     */
    public function unmappedMorphs(): array
    {
        $mapped = [];
        foreach ($this->relations as $relation) {
            if ($relation->type === RelationType::Morph) {
                $mapped["{$relation->to}.{$relation->morphName}"] = true;
            }
        }

        $unmapped = [];
        foreach ($this->tables as $table) {
            foreach ($table->morphNames as $morphName) {
                $pair = "{$table->name}.{$morphName}";
                if (! isset($mapped[$pair])) {
                    $unmapped[] = $pair;
                }
            }
        }

        return $unmapped;
    }
}
