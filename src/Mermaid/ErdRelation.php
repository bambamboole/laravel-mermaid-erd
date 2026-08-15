<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Mermaid;

readonly class ErdRelation
{
    public function __construct(
        public string $from,
        public string $to,
        public Cardinality $parent,
        public Cardinality $child,
        public string $label,
    ) {}

    public function render(): string
    {
        return "    {$this->from} {$this->parent->left()}--{$this->child->right()} {$this->to} : \"{$this->label}\"\n";
    }
}
