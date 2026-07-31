<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class ModelScan
{
    /**
     * @param  array<string, ModelMetadata>  $models  keyed by table name
     * @param  array<string, string[]>  $morphRelationships  "childTable.morphName" => target tables
     * @param  ScannedRelation[]  $relations  non-morph relations declared on models
     */
    public function __construct(
        public array $models = [],
        public array $morphRelationships = [],
        public array $relations = [],
    ) {}
}
