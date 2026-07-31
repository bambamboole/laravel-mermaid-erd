<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Illuminate\Support\Str;

class MermaidErdGenerator
{
    public function __construct(
        private readonly DatabaseInformationService $databaseInformationService,
        private readonly array $polymorphicRelationships = [],
        private readonly bool $guessRelationships = true,
    ) {}

    public function generate(): string
    {
        $tables = $this->databaseInformationService->getTables();
        $pivotTableNames = array_keys($this->detectPivotTables($tables));

        $totalColumns = 0;
        $tableDiagrams = [];
        $allPolymorphicPairs = [];
        foreach ($tables as $table) {
            $columns = $this->databaseInformationService->getColumns($table);
            $totalColumns += count($columns);
            $tableDiagrams[$table] = $this->generateTableDiagram($table, $columns);

            $columnNames = array_map(fn (array $col) => $col['name'], $columns);
            foreach ($this->detectPolymorphicPairs($columnNames) as $morphName) {
                $allPolymorphicPairs[] = "{$table}.{$morphName}";
            }
        }

        $tableCount = count($tables);
        $diagram = "---\ntitle: {$tableCount} tables · {$totalColumns} columns\n---\nerDiagram\n";

        foreach ($tableDiagrams as $tableDiagram) {
            $diagram .= $tableDiagram;
        }

        foreach ($tables as $table) {
            $diagram .= $this->generateRelationships($table, $tables, $pivotTableNames);

            if ($this->guessRelationships) {
                $columns = $this->databaseInformationService->getColumns($table);
                $diagram .= $this->generateGuessedRelationships($table, $columns, $tables);
            }
        }

        foreach ($this->polymorphicRelationships as $key => $targetTables) {
            [$table, $morphName] = explode('.', $key, 2);
            foreach ($targetTables as $targetTable) {
                $diagram .= "    {$targetTable} ||--o{ {$table} : \"morphMany via {$morphName}\"\n";
            }
        }

        $unmapped = array_diff($allPolymorphicPairs, array_keys($this->polymorphicRelationships));
        if ($unmapped !== []) {
            $diagram .= "%% Unmapped polymorphic relations (add to config 'mermaid-erd.polymorphic_relationships'):\n";
            foreach ($unmapped as $pair) {
                [$table, $morphName] = explode('.', $pair, 2);
                $diagram .= "%%   {$pair} ({$morphName}_type + {$morphName}_id)\n";
            }
        }

        return $diagram;
    }

    protected function generateTableDiagram(string $table, array $columns): string
    {
        $columnCount = count($columns);
        $diagram = "    {$table}[\"{$table} ({$columnCount})\"] {\n";

        $primaryKeyColumns = $this->getPrimaryKeyColumns($table);
        $foreignKeyColumns = $this->getForeignKeyColumns($table);
        $uniqueColumns = $this->getUniqueColumns($table);

        $columnNames = array_map(fn (array $col) => $col['name'], $columns);
        $polymorphicColumns = $this->detectPolymorphicColumns($columnNames);

        foreach ($columns as $column) {
            $name = $column['name'];
            $line = "        {$column['type_name']} {$name}";

            $constraints = [];
            if (in_array($name, $primaryKeyColumns)) {
                $constraints[] = 'PK';
            }
            if (in_array($name, $foreignKeyColumns)) {
                $constraints[] = 'FK';
            }
            if (in_array($name, $uniqueColumns) && !in_array($name, $primaryKeyColumns)) {
                $constraints[] = 'UK';
            }
            if ($constraints !== []) {
                $line .= ' '.implode(', ', $constraints);
            }

            $comments = [];
            if ($name === 'deleted_at') {
                $comments[] = 'soft-delete';
            }
            if (in_array($name, $polymorphicColumns)) {
                $comments[] = 'polymorphic';
            }
            if ($column['nullable']) {
                $comments[] = 'nullable';
            }
            if ($column['default'] !== null) {
                $comments[] = "default: {$column['default']}";
            }
            if ($comments !== []) {
                $line .= ' "'.implode(', ', $comments).'"';
            }

            $diagram .= $line."\n";
        }

        return $diagram."    }\n";
    }

    protected function generateRelationships(string $table, array $activeTables, array $pivotTableNames = []): string
    {
        $relationships = '';
        $foreignKeys = $this->databaseInformationService->getForeignKeys($table);
        $uniqueColumns = $this->getUniqueColumns($table);

        $columns = $this->databaseInformationService->getColumns($table);
        $nullableColumns = array_map(
            fn (array $col) => $col['name'],
            array_filter($columns, fn (array $col) => $col['nullable']),
        );

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey['foreign_table'];

            if (!in_array($foreignTable, $activeTables)) {
                continue;
            }

            $columnLabel = implode(', ', $foreignKey['columns']);
            $isOneToOne = count($foreignKey['columns']) === 1 && in_array($foreignKey['columns'][0], $uniqueColumns);
            $isNullable = array_intersect($foreignKey['columns'], $nullableColumns) !== [];
            $isSelfRef = $foreignTable === $table;

            $parentSide = $isNullable ? '|o' : '||';
            $childSide = $isOneToOne ? '||' : 'o{';
            $cardinality = "{$parentSide}--{$childSide}";

            if ($isSelfRef) {
                $label = 'self-ref';
            } else {
                $label = $isOneToOne ? 'has one' : 'has many';
            }
            $label .= " via {$columnLabel}";

            $onDelete = strtolower($foreignKey['on_delete'] ?? '');
            if ($onDelete !== '' && !in_array($onDelete, ['no action', 'restrict'])) {
                $label .= ", {$onDelete} delete";
            }

            if (in_array($table, $pivotTableNames)) {
                $label = "pivot, {$label}";
            }

            $relationships .= "    {$foreignTable} {$cardinality} {$table} : \"{$label}\"\n";
        }

        return $relationships;
    }

    protected function generateGuessedRelationships(string $table, array $columns, array $activeTables): string
    {
        $relationships = '';

        $foreignKeyColumns = $this->getForeignKeyColumns($table);
        $columnNames = array_map(fn (array $col) => $col['name'], $columns);
        $polymorphicColumns = $this->detectPolymorphicColumns($columnNames);

        $nullableColumns = array_map(
            fn (array $col) => $col['name'],
            array_filter($columns, fn (array $col) => $col['nullable']),
        );

        foreach ($columns as $column) {
            $name = $column['name'];

            if (!str_ends_with($name, '_id')) {
                continue;
            }

            if (in_array($name, $foreignKeyColumns)) {
                continue;
            }

            if (in_array($name, $polymorphicColumns)) {
                continue;
            }

            $singular = substr($name, 0, -3);
            $guessedTable = Str::plural($singular);

            if (!in_array($guessedTable, $activeTables)) {
                continue;
            }

            $parentSide = in_array($name, $nullableColumns) ? '|o' : '||';
            $cardinality = "{$parentSide}--o{";

            $relationships .= "    {$guessedTable} {$cardinality} {$table} : \"guessed has many via {$name}\"\n";
        }

        return $relationships;
    }

    /**
     * @return array<string, array{name: string, tables: array{0: string, 1: string}}>
     */
    private function detectPivotTables(array $tables): array
    {
        $pivots = [];

        foreach ($tables as $table) {
            $foreignKeys = $this->databaseInformationService->getForeignKeys($table);

            if (count($foreignKeys) !== 2) {
                continue;
            }

            $columns = $this->databaseInformationService->getColumns($table);
            $columnNames = array_map(fn (array $col) => $col['name'], $columns);
            $fkColumns = array_merge(...array_map(fn (array $fk) => $fk['columns'], $foreignKeys));

            $nonFkColumns = array_diff($columnNames, $fkColumns);
            $allowedNonFk = ['id', 'created_at', 'updated_at'];

            if (array_diff($nonFkColumns, $allowedNonFk) !== []) {
                continue;
            }

            $foreignTables = array_map(fn (array $fk) => $fk['foreign_table'], $foreignKeys);
            sort($foreignTables);

            $pivots[$table] = [
                'name' => $table,
                'tables' => $foreignTables,
            ];
        }

        return $pivots;
    }

    private function getPrimaryKeyColumns(string $table): array
    {
        $indexes = $this->databaseInformationService->getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['primary']) {
                return $index['columns'];
            }
        }

        return [];
    }

    private function getForeignKeyColumns(string $table): array
    {
        $foreignKeys = $this->databaseInformationService->getForeignKeys($table);

        if ($foreignKeys === []) {
            return [];
        }

        return array_unique(array_merge(...array_map(fn (array $fk) => $fk['columns'], $foreignKeys)));
    }

    /**
     * @return string[]
     */
    private function detectPolymorphicColumns(array $columnNames): array
    {
        $polymorphic = [];

        foreach ($columnNames as $name) {
            if (!str_ends_with($name, '_type')) {
                continue;
            }

            $morphName = substr($name, 0, -5);
            $idColumn = $morphName.'_id';

            if (in_array($idColumn, $columnNames)) {
                $polymorphic[] = $name;
                $polymorphic[] = $idColumn;
            }
        }

        return $polymorphic;
    }

    /**
     * @return string[] morph names (e.g. ['reviewable'])
     */
    private function detectPolymorphicPairs(array $columnNames): array
    {
        $pairs = [];

        foreach ($columnNames as $name) {
            if (!str_ends_with($name, '_type')) {
                continue;
            }

            $morphName = substr($name, 0, -5);
            $idColumn = $morphName.'_id';

            if (in_array($idColumn, $columnNames)) {
                $pairs[] = $morphName;
            }
        }

        return $pairs;
    }

    private function getUniqueColumns(string $table): array
    {
        $indexes = $this->databaseInformationService->getIndexes($table);
        $uniqueColumns = [];

        foreach ($indexes as $index) {
            if ($index['unique'] && count($index['columns']) === 1) {
                $uniqueColumns[] = $index['columns'][0];
            }
        }

        return $uniqueColumns;
    }
}
