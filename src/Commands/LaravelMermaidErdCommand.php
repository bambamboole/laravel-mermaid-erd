<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Commands;

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\FileWriter;
use Bambamboole\LaravelMermaidErd\MermaidErdRenderer;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class LaravelMermaidErdCommand extends Command
{
    public $signature = 'generate:mermaid-erd
        {--output= : Output mode: stdout or file}
        {--path= : File path for "file" output mode}
        {--connection= : Database connection to use}
        {--tables= : Comma-separated list of tables to include}
        {--exclude-tables= : Comma-separated list of tables to exclude, on top of the configured ignore list}';

    protected $description = 'Generate a Mermaid.js ER diagram from the database schema';

    public function handle(): int
    {
        $connection = $this->laravel->make('db')->connection($this->stringOption('connection'));

        $tables = $this->stringOption('tables');
        $onlyTables = $tables !== null ? array_map(trim(...), explode(',', $tables)) : [];

        $excludeTables = $this->stringOption('exclude-tables');
        $ignoreTables = array_merge(
            config('mermaid-erd.ignore_tables', []),
            $excludeTables !== null ? array_map(trim(...), explode(',', $excludeTables)) : [],
        );

        $service = new DatabaseInformationService(
            $connection,
            $ignoreTables,
            $onlyTables,
            config('mermaid-erd.schema'),
        );

        $diagram = (new MermaidErdRenderer)->render(SchemaBuilder::fromConfig($service)->build());

        $output = $this->stringOption('output') ?? select(
            label: 'How would you like to output the diagram?',
            options: ['stdout', 'file'],
            default: 'stdout',
        );

        return match ($output) {
            'file' => $this->outputToFile($diagram),
            default => $this->outputToStdout($diagram),
        };
    }

    private function outputToStdout(string $diagram): int
    {
        $this->info($diagram);

        return self::SUCCESS;
    }

    private function outputToFile(string $diagram): int
    {
        $path = $this->stringOption('path') ?? text(
            label: 'Where should the diagram be saved?',
            default: 'README.md',
            required: true,
        );

        if (!str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        (new FileWriter)->write($path, $diagram);

        $this->info("Diagram written to {$path}");

        return self::SUCCESS;
    }

    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
