<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class ScannedRelation
{
    /**
     * @param  string  $from  the referenced (parent) table
     * @param  string  $to  the table holding the foreign key column (child)
     * @param  string  $declaredAs  the Eloquent method that declared it (hasMany, hasOne, belongsTo)
     */
    public function __construct(
        public string $from,
        public string $to,
        public string $column,
        public string $declaredAs,
    ) {}
}
