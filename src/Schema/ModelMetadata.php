<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class ModelMetadata
{
    /**
     * @param  class-string  $class
     * @param  array<string, string>  $casts  attribute => cast (implicit key cast removed)
     * @param  string[]  $accessors  snake-cased attribute names
     * @param  string[]  $mutators  snake-cased attribute names
     */
    public function __construct(
        public string $class,
        public array $casts = [],
        public array $accessors = [],
        public array $mutators = [],
    ) {}
}
