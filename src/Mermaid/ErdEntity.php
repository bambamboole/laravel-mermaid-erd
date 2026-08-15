<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Mermaid;

readonly class ErdEntity
{
    /**
     * @param  ErdAttribute[]  $attributes
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $attributes = [],
    ) {}

    public function render(): string
    {
        $block = "    {$this->id}[\"{$this->label}\"] {\n";

        foreach ($this->attributes as $attribute) {
            $block .= $attribute->render();
        }

        return $block."    }\n";
    }
}
