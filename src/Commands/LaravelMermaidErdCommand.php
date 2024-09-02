<?php declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Commands;

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LaravelMermaidErdCommand extends Command
{
    public $signature = 'generate:mermaid-erd';

    protected $description = 'Generate a Mermaid.js ER diagram from the database schema';

    public function handle(MermaidErdGenerator $generator)
    {
        $this->info($generator->generate());
    }
}
