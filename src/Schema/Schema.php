<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

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
     * Lightweight graph representation for client-side filtering.
     *
     * @return array{tables: array<string, string[]>, edges: array<int, array{0: string, 1: string}>}
     */
    public function toGraph(): array
    {
        $tables = [];
        foreach ($this->tables as $table) {
            $tables[$table->name] = array_map(fn (Column $column): string => $column->name, $table->columns);
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

        return ['tables' => $tables, 'edges' => $edges];
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
                if (!isset($mapped[$pair])) {
                    $unmapped[] = $pair;
                }
            }
        }

        return $unmapped;
    }
}
