<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Support\Str;

class SchemaBuilder
{
    private const array PIVOT_EXTRA_COLUMNS = ['id', 'created_at', 'updated_at'];

    public function __construct(
        private readonly DatabaseInformationService $databaseInformationService,
        private readonly array $polymorphicRelationships = [],
        private readonly bool $guessRelationships = true,
    ) {}

    public function build(): Schema
    {
        $tableNames = $this->databaseInformationService->getTables();

        $tables = [];
        $meta = [];

        foreach ($tableNames as $tableName) {
            $rawColumns = $this->databaseInformationService->getColumns($tableName);
            $indexes = $this->databaseInformationService->getIndexes($tableName);
            $foreignKeys = $this->databaseInformationService->getForeignKeys($tableName);

            $columnNames = array_map(fn (array $column): string => $column['name'], $rawColumns);

            $primaryKeyColumns = [];
            $uniqueColumns = [];
            foreach ($indexes as $index) {
                if ($index['primary'] && $primaryKeyColumns === []) {
                    $primaryKeyColumns = $index['columns'];
                }
                if ($index['unique'] && count($index['columns']) === 1) {
                    $uniqueColumns[] = $index['columns'][0];
                }
            }

            $foreignKeyColumns = $foreignKeys === []
                ? []
                : array_unique(array_merge(...array_map(fn (array $fk): array => $fk['columns'], $foreignKeys)));

            [$morphNames, $polymorphicColumns] = $this->detectMorphs($columnNames);

            $columns = [];
            $nullableColumns = [];
            foreach ($rawColumns as $rawColumn) {
                $name = $rawColumn['name'];
                if ($rawColumn['nullable']) {
                    $nullableColumns[] = $name;
                }

                $columns[] = new Column(
                    name: $name,
                    type: $rawColumn['type_name'],
                    nullable: (bool) $rawColumn['nullable'],
                    default: $rawColumn['default'] !== null ? (string) $rawColumn['default'] : null,
                    primaryKey: in_array($name, $primaryKeyColumns),
                    foreignKey: in_array($name, $foreignKeyColumns),
                    unique: in_array($name, $uniqueColumns),
                    softDelete: $name === 'deleted_at',
                    polymorphic: in_array($name, $polymorphicColumns),
                );
            }

            $tables[$tableName] = new Table(
                name: $tableName,
                columns: $columns,
                pivot: $this->isPivot($foreignKeys, $columnNames, $foreignKeyColumns),
                morphNames: $morphNames,
            );

            $meta[$tableName] = [
                'foreignKeys' => $foreignKeys,
                'uniqueColumns' => $uniqueColumns,
                'nullableColumns' => $nullableColumns,
                'foreignKeyColumns' => $foreignKeyColumns,
                'polymorphicColumns' => $polymorphicColumns,
            ];
        }

        return new Schema($tables, $this->buildRelations($tableNames, $tables, $meta));
    }

    private function buildRelations(array $tableNames, array $tables, array $meta): array
    {
        $relations = [];

        foreach ($tableNames as $tableName) {
            foreach ($meta[$tableName]['foreignKeys'] as $foreignKey) {
                $foreignTable = $foreignKey['foreign_table'];

                if (!in_array($foreignTable, $tableNames)) {
                    continue;
                }

                $relations[] = new Relation(
                    from: $foreignTable,
                    to: $tableName,
                    type: RelationType::ForeignKey,
                    columns: $foreignKey['columns'],
                    nullable: array_intersect($foreignKey['columns'], $meta[$tableName]['nullableColumns']) !== [],
                    oneToOne: count($foreignKey['columns']) === 1
                        && in_array($foreignKey['columns'][0], $meta[$tableName]['uniqueColumns']),
                    onDelete: $this->normalizeOnDelete($foreignKey['on_delete'] ?? null),
                );
            }

            if ($this->guessRelationships) {
                array_push($relations, ...$this->guessRelations($tableName, $tableNames, $tables, $meta));
            }
        }

        foreach ($this->polymorphicRelationships as $key => $targetTables) {
            [$tableName, $morphName] = explode('.', (string) $key, 2);
            foreach ($targetTables as $targetTable) {
                $relations[] = new Relation(
                    from: $targetTable,
                    to: $tableName,
                    type: RelationType::Morph,
                    morphName: $morphName,
                );
            }
        }

        return $relations;
    }

    private function guessRelations(string $tableName, array $tableNames, array $tables, array $meta): array
    {
        $relations = [];

        foreach ($tables[$tableName]->columns as $column) {
            if (!str_ends_with($column->name, '_id')) {
                continue;
            }

            if (in_array($column->name, $meta[$tableName]['foreignKeyColumns'])) {
                continue;
            }

            if (in_array($column->name, $meta[$tableName]['polymorphicColumns'])) {
                continue;
            }

            $guessedTable = Str::plural(substr($column->name, 0, -3));

            if (!in_array($guessedTable, $tableNames)) {
                continue;
            }

            $relations[] = new Relation(
                from: $guessedTable,
                to: $tableName,
                type: RelationType::Guessed,
                columns: [$column->name],
                nullable: $column->nullable,
            );
        }

        return $relations;
    }

    /**
     * @return array{0: string[], 1: string[]} [morph names, participating column names]
     */
    private function detectMorphs(array $columnNames): array
    {
        $morphNames = [];
        $polymorphicColumns = [];

        foreach ($columnNames as $name) {
            if (!str_ends_with($name, '_type')) {
                continue;
            }

            $morphName = substr($name, 0, -5);
            $idColumn = $morphName.'_id';

            if (in_array($idColumn, $columnNames)) {
                $morphNames[] = $morphName;
                $polymorphicColumns[] = $name;
                $polymorphicColumns[] = $idColumn;
            }
        }

        return [$morphNames, $polymorphicColumns];
    }

    private function isPivot(array $foreignKeys, array $columnNames, array $foreignKeyColumns): bool
    {
        if (count($foreignKeys) !== 2) {
            return false;
        }

        return array_diff(array_diff($columnNames, $foreignKeyColumns), self::PIVOT_EXTRA_COLUMNS) === [];
    }

    private function normalizeOnDelete(?string $onDelete): ?string
    {
        $onDelete = strtolower($onDelete ?? '');

        if ($onDelete === '' || in_array($onDelete, ['no action', 'restrict'])) {
            return null;
        }

        return $onDelete;
    }
}
