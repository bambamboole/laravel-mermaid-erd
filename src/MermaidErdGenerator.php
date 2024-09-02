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
            // Assuming foreign key is named as <table>_id for simplicity
            if (preg_match('/(.+)_id$/', $foreignKey->Column_name, $matches)) {
                $relatedTable = $matches[1];
                $relationships .= "    {$relatedTable}s ||--o{ {$table} : \"has many via {$foreignKey->Column_name}\"\n";
            }
        }

        return $relationships;
    }
}
