<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Mermaid;

readonly class ErdDocument
{
    /**
     * @param  ErdEntity[]  $entities
     * @param  ErdRelation[]  $relations
     * @param  string[]  $trailingComments  emitted as %% comment lines after the relations
     */
    public function __construct(
        public string $title,
        public array $entities = [],
        public array $relations = [],
        public array $trailingComments = [],
    ) {}

    public function render(): string
    {
        $document = "---\ntitle: {$this->title}\n---\nerDiagram\n";

        foreach ($this->entities as $entity) {
            $document .= $entity->render();
        }

        foreach ($this->relations as $relation) {
            $document .= $relation->render();
        }

        foreach ($this->trailingComments as $comment) {
            $document .= "%% {$comment}\n";
        }

        return $document;
    }
}
