<?php

namespace Bambamboole\LaravelMermaidErd\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bambamboole\LaravelMermaidErd\LaravelMermaidErd
 */
class LaravelMermaidErd extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bambamboole\LaravelMermaidErd\LaravelMermaidErd::class;
    }
}
