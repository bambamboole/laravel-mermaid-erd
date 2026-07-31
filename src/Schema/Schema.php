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
