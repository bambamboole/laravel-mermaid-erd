<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

class MermaidErdGenerator
{
    public function __construct(
        private readonly DatabaseInformationService $databaseInformationService,
        private readonly array $polymorphicRelationships = [],
    ) {}

    public function generate(): string
    {
        $tables = $this->databaseInformationService->getTables();
        $pivots = $this->detectPivotTables($tables);
        $nonPivotTables = array_values(array_filter($tables, fn (string $table) => !isset($pivots[$table])));

        $totalColumns = 0;
        $tableDiagrams = [];
        foreach ($nonPivotTables as $table) {
            $columns = $this->databaseInformationService->getColumns($table);
            $totalColumns += count($columns);
            $tableDiagrams[$table] = $this->generateTableDiagram($table, $columns);
        }

        $tableCount = count($nonPivotTables);
        $diagram = "---\ntitle: {$tableCount} tables · {$totalColumns} columns\n---\nerDiagram\n";

        foreach ($tableDiagrams as $tableDiagram) {
            $diagram .= $tableDiagram;
        }

        foreach ($nonPivotTables as $table) {
            $diagram .= $this->generateRelationships($table, $tables);
        }

        foreach ($pivots as $pivot) {
            $diagram .= "    {$pivot['tables'][0]} }o--o{ {$pivot['tables'][1]} : \"{$pivot['name']}\"\n";
        }

        foreach ($this->polymorphicRelationships as $key => $targetTables) {
            [$table, $morphName] = explode('.', $key, 2);
            foreach ($targetTables as $targetTable) {
                $diagram .= "    {$targetTable} ||--o{ {$table} : \"morphMany via {$morphName}\"\n";
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

        $diagram .= "    }\n";

        return $diagram;
    }

    protected function generateRelationships(string $table, array $activeTables): string
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

            $onDelete = $foreignKey['on_delete'] ?? '';
            if ($onDelete !== '' && !in_array(strtolower($onDelete), ['no action', 'restrict'])) {
                $label .= ", {$onDelete} delete";
            }

            $relationships .= "    {$foreignTable} {$cardinality} {$table} : \"{$label}\"\n";
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
