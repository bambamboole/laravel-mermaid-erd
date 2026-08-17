<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Mermaid;

enum Cardinality
{
    case ZeroOrOne;
    case ExactlyOne;
    case ZeroOrMore;

    public function left(): string
    {
        return match ($this) {
            self::ZeroOrOne => '|o',
            self::ExactlyOne => '||',
            self::ZeroOrMore => '}o',
        };
    }

    public function right(): string
    {
        return match ($this) {
            self::ZeroOrOne => 'o|',
            self::ExactlyOne => '||',
            self::ZeroOrMore => 'o{',
        };
    }
}
