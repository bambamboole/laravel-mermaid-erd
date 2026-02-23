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
        return $this->generateMermaidDiagram($this->databaseInformationService->getTables());
    }

    protected function generateMermaidDiagram($tables): string
    {
        $diagram = "erDiagram\n";

        foreach ($tables as $table) {
            $diagram .= $this->generateTableDiagram($table);
        }

        foreach ($tables as $table) {
            $diagram .= $this->generateRelationships($table);
        }

        return $diagram;
    }

    protected function generateTableDiagram($table)
    {
        $diagram = "    {$table} {\n";

        $columns = $this->databaseInformationService->getColumnListing($table);
        foreach ($columns as $column) {
            $type = $this->databaseInformationService->getColumnType($table, $column);
            $diagram .= "        {$type} {$column}\n";
        }

        $diagram .= "    }\n";

        return $diagram;
    }

    protected function generateRelationships($table)
    {
        $relationships = '';
        $foreignKeys = $this->databaseInformationService->getForeignKeys($table);

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = $foreignKey['foreign_table'];
            $columnName = $foreignKey['columns'][0];
            $relationships .= "    {$foreignTable} ||--o{ {$table} : \"has many via {$columnName}\"\n";
        }

        return $relationships;
    }
}
