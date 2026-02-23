<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

class MermaidErdGenerator
{
    public function __construct(
        private readonly DatabaseInformationService $databaseInformationService,
    ) {}

    public function generate(): string
    {
        $tables = $this->databaseInformationService->getTables();
        $pivots = $this->detectPivotTables($tables);
        $nonPivotTables = array_values(array_filter($tables, fn (string $table) => !isset($pivots[$table])));

        $diagram = "erDiagram\n";

        foreach ($nonPivotTables as $table) {
            $diagram .= $this->generateTableDiagram($table);
        }

        foreach ($nonPivotTables as $table) {
            $diagram .= $this->generateRelationships($table, $tables);
        }

        foreach ($pivots as $pivot) {
            $diagram .= "    {$pivot['tables'][0]} }o--o{ {$pivot['tables'][1]} : \"{$pivot['name']}\"\n";
        }

        return $diagram;
    }

    protected function generateTableDiagram(string $table): string
    {
        $diagram = "    {$table} {\n";

        $primaryKeyColumns = $this->getPrimaryKeyColumns($table);
        $foreignKeyColumns = $this->getForeignKeyColumns($table);
        $uniqueColumns = $this->getUniqueColumns($table);

        $columns = $this->databaseInformationService->getColumns($table);
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

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey['foreign_table'];

            if (!in_array($foreignTable, $activeTables)) {
                continue;
            }

            $columnName = $foreignKey['columns'][0];
            $isOneToOne = in_array($columnName, $uniqueColumns);
            $cardinality = $isOneToOne ? '||--||' : '||--o{';
            $label = $isOneToOne ? 'has one' : 'has many';
            $label .= " via {$columnName}";

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
