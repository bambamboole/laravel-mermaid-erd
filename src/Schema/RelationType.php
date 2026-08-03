<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Schema;

enum RelationType
{
    case ForeignKey;
    case Eloquent;
    case Guessed;
    case Morph;
}
