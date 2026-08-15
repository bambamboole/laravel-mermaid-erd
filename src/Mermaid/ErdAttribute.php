<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Mermaid;

readonly class ErdAttribute
{
    /**
     * @param  string[]  $keys  constraint markers (PK, FK, UK)
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $keys = [],
        public ?string $comment = null,
    ) {}

    public function render(): string
    {
        $line = "        {$this->type} {$this->name}";

        if ($this->keys !== []) {
            $line .= ' '.implode(', ', $this->keys);
        }

        if ($this->comment !== null) {
            $line .= " \"{$this->comment}\"";
        }

        return $line."\n";
    }
}
