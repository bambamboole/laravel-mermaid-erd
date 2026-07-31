<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Illuminate\Database\Connection;

/**
 * Shapes as returned by Laravel's schema builder (unsealed — drivers add keys).
 *
 * @phpstan-type ForeignKeyRow array{columns: string[], foreign_table: string, on_delete: string|null, ...}
 * @phpstan-type ColumnRow array{name: string, type_name: string, nullable: bool, default: string|null, ...}
 * @phpstan-type IndexRow array{columns: string[], primary: bool, unique: bool, ...}
 */
class DatabaseInformationService
{
    /**
     * @param  string[]  $ignoreTables
     * @param  string[]  $onlyTables
     */
    public function __construct(
        private readonly Connection $db,
        private readonly array $ignoreTables = [],
        private readonly array $onlyTables = [],
        private readonly ?string $schema = null,
    ) {}

    /**
     * @return string[]
     */
    public function getTables(): array
    {
        $tables = $this->db->getSchemaBuilder()->getTables($this->getSchemaName());
        $tableNames = array_map(fn (array $table): string => $table['name'], $tables);

        if ($this->onlyTables !== []) {
            return array_values(array_filter($tableNames, fn (string $tableName): bool => in_array($tableName, $this->onlyTables)));
        }

        return array_values(array_filter($tableNames, fn (string $tableName): bool => !in_array($tableName, $this->ignoreTables)));
    }

    /**
     * @return list<ForeignKeyRow>
     */
    public function getForeignKeys(string $table): array
    {
        return $this->db->getSchemaBuilder()->getForeignKeys($table);
    }

    /**
     * @return list<ColumnRow>
     */
    public function getColumns(string $table): array
    {
        return $this->db->getSchemaBuilder()->getColumns($table);
    }

    /**
     * @return list<IndexRow>
     */
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
