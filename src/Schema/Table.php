<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class Table
{
    /**
     * @param  Column[]  $columns
     * @param  string[]  $morphNames  detected morph pairs, e.g. ['reviewable']
     */
    public function __construct(
        public string $name,
        public array $columns,
        public bool $pivot = false,
        public array $morphNames = [],
    ) {}
}
