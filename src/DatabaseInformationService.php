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
        $property = "Tables_in_{$this->db->getDatabaseName()}";
        $tableNames = array_map(fn ($table) => $table->$property, $this->db->select('SHOW TABLES'));

        return array_filter($tableNames, fn ($tableName) => ! in_array($tableName, $this->ignoreTables));
    }

    public function getForeignKeys(string $table): array
    {
        return $this->db->select("SHOW KEYS FROM `{$table}` WHERE Key_name like '%_foreign'");
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
