<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

readonly class Relation
{
    /**
     * @param  string  $from  the referenced (parent) table
     * @param  string  $to  the table holding the reference (child)
     * @param  string[]  $columns  referencing column(s) on the child table
     * @param  ?string  $onDelete  lowercased action; null when absent or a no-op (no action, restrict)
     */
    public function __construct(
        public string $from,
        public string $to,
        public RelationType $type,
        public array $columns = [],
        public bool $nullable = false,
        public bool $oneToOne = false,
        public ?string $onDelete = null,
        public ?string $morphName = null,
    ) {}

    public function selfReferential(): bool
    {
        return $this->from === $this->to;
    }
}
