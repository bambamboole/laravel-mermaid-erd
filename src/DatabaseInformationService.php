<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Illuminate\Database\Connection;

class DatabaseInformationService
{
    public function __construct(
        private readonly Connection $db,
        private readonly array $ignoreTables = [],
        private readonly array $onlyTables = [],
        private readonly ?string $schema = null,
    ) {}

    public function getTables(): array
    {
        $tables = $this->db->getSchemaBuilder()->getTables($this->getSchemaName());
        $tableNames = array_map(fn (array $table): string => $table['name'], $tables);

        if ($this->onlyTables !== []) {
            return array_values(array_filter($tableNames, fn (string $tableName): bool => in_array($tableName, $this->onlyTables)));
        }

        return array_values(array_filter($tableNames, fn (string $tableName): bool => !in_array($tableName, $this->ignoreTables)));
    }

    public function getForeignKeys(string $table): array
    {
        return $this->db->getSchemaBuilder()->getForeignKeys($table);
    }

    public function getColumns(string $table): array
    {
        return $this->db->getSchemaBuilder()->getColumns($table);
    }

    public function getIndexes(string $table): array
    {
        return $this->db->getSchemaBuilder()->getIndexes($table);
    }

    private function getSchemaName(): ?string
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        if ($this->db->getDriverName() === 'mysql') {
            return $this->db->getDatabaseName();
        }

        return null;
    }
}
