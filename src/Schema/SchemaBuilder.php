<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type ForeignKeyRow from DatabaseInformationService
 */
class SchemaBuilder
{
    private const array PIVOT_EXTRA_COLUMNS = ['id', 'created_at', 'updated_at'];

    /**
     * @param  array<string, string[]>  $polymorphicRelationships
     */
    public function __construct(
        private readonly DatabaseInformationService $databaseInformationService,
        private readonly array $polymorphicRelationships = [],
        private readonly bool $guessRelationships = true,
        private readonly ?ModelScan $models = null,
    ) {}

    public static function fromConfig(DatabaseInformationService $databaseInformationService): self
    {
        return new self(
            $databaseInformationService,
            config('mermaid-erd.polymorphic_relationships', []),
            config('mermaid-erd.guess_relationships', true),
            config('mermaid-erd.models.enabled', true) ? app(ModelScanner::class)->scan() : null,
        );
    }

    public function build(): Schema
    {
        $tableNames = $this->databaseInformationService->getTables();

        $tables = [];
        $foreignKeysByTable = [];
        $indexesByTable = [];

        foreach ($tableNames as $tableName) {
            $rawColumns = $this->databaseInformationService->getColumns($tableName);
            $indexes = $this->databaseInformationService->getIndexes($tableName);
            $foreignKeys = $foreignKeysByTable[$tableName] = $this->databaseInformationService->getForeignKeys($tableName);

            // Ordered column lists; expression indexes report no columns and are skipped.
            $indexColumns = $indexesByTable[$tableName] = array_values(array_filter(
                array_map(fn (array $index): array => $index['columns'], $indexes),
            ));

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

            $metadata = $this->models?->models[$tableName] ?? null;
            $casts = $metadata->casts ?? [];
            $accessors = $metadata->accessors ?? [];
            $mutators = $metadata->mutators ?? [];

            $columns = [];
            foreach ($rawColumns as $rawColumn) {
                $name = $rawColumn['name'];

                $columns[$name] = new Column(
                    name: $name,
                    type: $rawColumn['type_name'],
                    nullable: (bool) $rawColumn['nullable'],
                    default: $rawColumn['default'] !== null ? (string) $rawColumn['default'] : null,
                    primaryKey: in_array($name, $primaryKeyColumns),
                    foreignKey: in_array($name, $foreignKeyColumns),
                    unique: in_array($name, $uniqueColumns),
                    softDelete: $name === 'deleted_at',
                    polymorphic: in_array($name, $polymorphicColumns),
                    cast: $casts[$name] ?? null,
                    accessor: in_array($name, $accessors),
                    mutator: in_array($name, $mutators),
                );
            }

            $tables[$tableName] = new Table(
                name: $tableName,
                columns: $columns,
                pivot: $this->isPivot($foreignKeys, $columnNames, $foreignKeyColumns),
                morphNames: $morphNames,
                unindexedMorphs: array_values(array_filter(
                    $morphNames,
                    fn (string $morphName): bool => !$this->morphPairIndexed($indexColumns, $morphName),
                )),
                model: $metadata?->class,
            );
        }

        return new Schema($tables, $this->buildRelations($tableNames, $tables, $foreignKeysByTable, $indexesByTable));
    }

    /**
     * @param  string[]  $tableNames
     * @param  array<string, Table>  $tables
     * @param  array<string, list<ForeignKeyRow>>  $foreignKeysByTable
     * @param  array<string, list<string[]>>  $indexesByTable
     * @return list<Relation>
     */
    private function buildRelations(array $tableNames, array $tables, array $foreignKeysByTable, array $indexesByTable): array
    {
        $relations = [];

        $scannedByChild = [];
        foreach ($this->models->relations ?? [] as $scanned) {
            $scannedByChild[$scanned->to][] = $scanned;
        }

        foreach ($tableNames as $tableName) {
            $columns = $tables[$tableName]->columns;
            $indexes = $indexesByTable[$tableName];

            foreach ($foreignKeysByTable[$tableName] as $foreignKey) {
                $foreignTable = $foreignKey['foreign_table'];

                if (!in_array($foreignTable, $tableNames)) {
                    continue;
                }

                $relations[] = new Relation(
                    from: $foreignTable,
                    to: $tableName,
                    type: RelationType::ForeignKey,
                    columns: $foreignKey['columns'],
                    nullable: array_filter($foreignKey['columns'], fn (string $name): bool => $columns[$name]->nullable) !== [],
                    oneToOne: count($foreignKey['columns']) === 1
                        && $columns[$foreignKey['columns'][0]]->unique,
                    onDelete: $this->normalizeOnDelete($foreignKey['on_delete'] ?? null),
                    indexed: $this->hasSupportingIndex($indexes, $foreignKey['columns']),
                );
            }

            $modelCoveredColumns = [];
            foreach ($scannedByChild[$tableName] ?? [] as $scanned) {
                $column = $columns[$scanned->column] ?? null;

                // A foreign key constraint is authoritative; the model relation
                // only fills in where the schema has none.
                if ($column === null || $column->foreignKey || !in_array($scanned->from, $tableNames)) {
                    continue;
                }

                $modelCoveredColumns[] = $scanned->column;
                $relations[] = new Relation(
                    from: $scanned->from,
                    to: $tableName,
                    type: RelationType::Eloquent,
                    columns: [$scanned->column],
                    nullable: $column->nullable,
                    oneToOne: $scanned->declaredAs === 'hasOne' || $column->unique,
                    declaredAs: $scanned->declaredAs,
                    indexed: $this->hasSupportingIndex($indexes, [$scanned->column]),
                );
            }

            if ($this->guessRelationships) {
                array_push($relations, ...$this->guessRelations($tableName, $tableNames, $tables, $indexes, $modelCoveredColumns));
            }
        }

        foreach ($this->morphRelationships() as $key => $targetTables) {
            [$tableName, $morphName] = explode('.', $key, 2);
            foreach ($targetTables as $targetTable) {
                if (!isset($tables[$tableName]) || !isset($tables[$targetTable])) {
                    continue;
                }

                $relations[] = new Relation(
                    from: $targetTable,
                    to: $tableName,
                    type: RelationType::Morph,
                    morphName: $morphName,
                    indexed: $this->morphPairIndexed($indexesByTable[$tableName], $morphName),
                );
            }
        }

        return $relations;
    }

    /**
     * Scanned morph relations merged with the configured mappings.
     *
     * @return array<string, string[]>
     */
    private function morphRelationships(): array
    {
        $merged = $this->models->morphRelationships ?? [];

        foreach ($this->polymorphicRelationships as $key => $targets) {
            $merged[$key] = array_values(array_unique(array_merge($merged[$key] ?? [], $targets)));
        }

        return $merged;
    }

    /**
     * @param  string[]  $tableNames
     * @param  array<string, Table>  $tables
     * @param  list<string[]>  $indexes
     * @param  string[]  $modelCoveredColumns
     * @return list<Relation>
     */
    private function guessRelations(string $tableName, array $tableNames, array $tables, array $indexes, array $modelCoveredColumns = []): array
    {
        $relations = [];

        foreach ($tables[$tableName]->columns as $column) {
            if (!str_ends_with($column->name, '_id')) {
                continue;
            }

            if ($column->foreignKey || $column->polymorphic || in_array($column->name, $modelCoveredColumns)) {
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
                indexed: $this->hasSupportingIndex($indexes, [$column->name]),
            );
        }

        return $relations;
    }

    /**
     * True when the leading columns of some index are exactly the given
     * columns (any order among them) — the leftmost-prefix rule.
     *
     * @param  list<string[]>  $indexes  ordered column lists
     * @param  string[]  $columns
     */
    private function hasSupportingIndex(array $indexes, array $columns): bool
    {
        foreach ($indexes as $indexColumns) {
            if (count($indexColumns) >= count($columns)
                && array_diff($columns, array_slice($indexColumns, 0, count($columns))) === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Eloquent queries morphs as `type = ? AND id = ?`: covered by an index
     * leading with the id column or with the pair in either order.
     *
     * @param  list<string[]>  $indexes
     */
    private function morphPairIndexed(array $indexes, string $morphName): bool
    {
        return $this->hasSupportingIndex($indexes, ["{$morphName}_id"])
            || $this->hasSupportingIndex($indexes, ["{$morphName}_type", "{$morphName}_id"]);
    }

    /**
     * @param  string[]  $columnNames
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

    /**
     * @param  list<ForeignKeyRow>  $foreignKeys
     * @param  string[]  $columnNames
     * @param  string[]  $foreignKeyColumns
     */
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
