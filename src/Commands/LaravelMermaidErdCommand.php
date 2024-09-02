<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Commands;

use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Console\Command;

class LaravelMermaidErdCommand extends Command
{
    public $signature = 'generate:mermaid-erd';

    protected $description = 'Generate a Mermaid.js ER diagram from the database schema';

    public function handle(MermaidErdGenerator $generator)
    {
        $this->info($generator->generate());
    }
}
