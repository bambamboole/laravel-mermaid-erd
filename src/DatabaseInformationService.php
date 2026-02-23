<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd;

use Illuminate\Database\Connection;

class DatabaseInformationService
{
    public function __construct(
        private readonly Connection $db,
        private readonly array $ignoreTables = [],
    ) {}

    public function getTables(): array
    {
        $tables = $this->db->getSchemaBuilder()->getTables();
        $tableNames = array_map(fn (array $table) => $table['name'], $tables);

        return array_values(array_filter($tableNames, fn ($tableName) => ! in_array($tableName, $this->ignoreTables)));
    }

    public function getForeignKeys(string $table): array
    {
        return $this->db->getSchemaBuilder()->getForeignKeys($table);
    }

    public function getColumnListing(string $table): array
    {
        return $this->db->getSchemaBuilder()->getColumnListing($table);
    }

    public function getColumnType(string $table, string $column): string
    {
        return $this->db->getSchemaBuilder()->getColumnType($table, $column);
    }
}
