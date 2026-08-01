<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class Table
{
    /**
     * @param  array<string, Column>  $columns  keyed by column name
     * @param  string[]  $morphNames  detected morph pairs, e.g. ['reviewable']
     * @param  string[]  $unindexedMorphs  morph names whose column pair lacks a supporting index
     * @param  class-string|null  $model  the Eloquent model backing this table, when discovered
     */
    public function __construct(
        public string $name,
        public array $columns,
        public bool $pivot = false,
        public array $morphNames = [],
        public array $unindexedMorphs = [],
        public ?string $model = null,
    ) {}
}
