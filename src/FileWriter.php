<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

use Illuminate\Filesystem\Filesystem;

class FileWriter
{
    private const string START_TAG = '<!-- mermaid-erd-start -->';

    private const string END_TAG = '<!-- mermaid-erd-end -->';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function write(string $path, string $diagram): void
    {
        $mermaidBlock = self::START_TAG."\n```mermaid\n{$diagram}```\n".self::END_TAG;

        if (!$this->filesystem->exists($path)) {
            $this->filesystem->put($path, "## ERD\n\n{$mermaidBlock}\n");

            return;
        }

        $content = $this->filesystem->get($path);

        if (str_contains($content, self::START_TAG) && str_contains($content, self::END_TAG)) {
            $pattern = '/'.preg_quote(self::START_TAG, '/').'.*?'.preg_quote(self::END_TAG, '/').'/s';
            $content = preg_replace($pattern, $mermaidBlock, $content);
        } else {
            $content = rtrim($content)."\n\n## ERD\n\n{$mermaidBlock}\n";
        }

        $this->filesystem->put($path, $content);
    }
}
