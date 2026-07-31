<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class Table
{
    /**
     * @param  Column[]  $columns
     * @param  string[]  $morphNames  detected morph pairs, e.g. ['reviewable']
     * @param  class-string|null  $model  the Eloquent model backing this table, when discovered
     */
    public function __construct(
        public string $name,
        public array $columns,
        public bool $pivot = false,
        public array $morphNames = [],
        public ?string $model = null,
    ) {}
}
