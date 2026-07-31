<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class Column
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public ?string $default = null,
        public bool $primaryKey = false,
        public bool $foreignKey = false,
        public bool $unique = false,
        public bool $softDelete = false,
        public bool $polymorphic = false,
        public ?string $cast = null,
        public bool $accessor = false,
        public bool $mutator = false,
    ) {}
}
