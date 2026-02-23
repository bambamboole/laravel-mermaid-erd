<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Commands;

use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class LaravelMermaidErdCommand extends Command
{
    public $signature = 'generate:mermaid-erd
        {--output= : Output mode: stdout, file, or readme}
        {--path= : File path for "file" or "readme" output mode}';

    protected $description = 'Generate a Mermaid.js ER diagram from the database schema';

    public function handle(MermaidErdGenerator $generator): int
    {
        $diagram = $generator->generate();

        $output = $this->option('output') ?? select(
            label: 'How would you like to output the diagram?',
            options: ['stdout', 'file', 'readme'],
            default: 'stdout',
        );

        return match ($output) {
            'stdout' => $this->outputToStdout($diagram),
            'file' => $this->outputToFile($diagram),
            'readme' => $this->outputToReadme($diagram),
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
            default: 'mermaid-erd.md',
            required: true,
        );

        $content = "```mermaid\n{$diagram}```\n";

        file_put_contents($path, $content);
        $this->info("Diagram written to {$path}");

        return self::SUCCESS;
    }

    private function outputToReadme(string $diagram): int
    {
        $path = $this->option('path') ?? text(
            label: 'Path to your README file?',
            default: base_path('README.md'),
            required: true,
        );

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $readme = file_get_contents($path);
        $startTag = '<!-- mermaid-erd-start -->';
        $endTag = '<!-- mermaid-erd-end -->';

        $mermaidBlock = "{$startTag}\n```mermaid\n{$diagram}```\n{$endTag}";

        if (str_contains($readme, $startTag) && str_contains($readme, $endTag)) {
            $pattern = '/'.preg_quote($startTag, '/').'.*?'.preg_quote($endTag, '/').'/s';
            $readme = preg_replace($pattern, $mermaidBlock, $readme);
        } else {
            $readme = rtrim($readme)."\n\n## ERD\n\n{$mermaidBlock}\n";
        }

        file_put_contents($path, $readme);
        $this->info("Diagram injected into {$path}");

        return self::SUCCESS;
    }
}
