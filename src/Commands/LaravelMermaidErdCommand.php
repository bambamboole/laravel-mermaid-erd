<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Commands;

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\FileWriter;
use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class LaravelMermaidErdCommand extends Command
{
    public $signature = 'generate:mermaid-erd
        {--output= : Output mode: stdout or file}
        {--path= : File path for "file" output mode}
        {--connection= : Database connection to use}
        {--tables= : Comma-separated list of tables to include}';

    protected $description = 'Generate a Mermaid.js ER diagram from the database schema';

    public function handle(): int
    {
        $connectionName = $this->option('connection');
        $connection = $this->laravel->make('db')->connection($connectionName);

        $onlyTables = $this->option('tables')
            ? array_map('trim', explode(',', $this->option('tables')))
            : [];

        $service = new DatabaseInformationService(
            $connection,
            config('mermaid-erd.ignore_tables', []),
            $onlyTables,
        );

        $generator = new MermaidErdGenerator($service);
        $diagram = $generator->generate();

        $output = $this->option('output') ?? select(
            label: 'How would you like to output the diagram?',
            options: ['stdout', 'file'],
            default: 'stdout',
        );

        return match ($output) {
            'stdout' => $this->outputToStdout($diagram),
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
        $path = $this->option('path') ?? text(
            label: 'Where should the diagram be saved?',
            default: 'README.md',
            required: true,
        );

        if (!str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        $writer = new FileWriter(new Filesystem);
        $writer->write($path, $diagram);

        $this->info("Diagram written to {$path}");

        return self::SUCCESS;
    }
}
