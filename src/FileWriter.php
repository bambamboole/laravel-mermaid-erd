<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd;

class FileWriter
{
    private const string START_TAG = '<!-- mermaid-erd-start -->';

    private const string END_TAG = '<!-- mermaid-erd-end -->';

    public function write(string $path, string $diagram): void
    {
        if (str_ends_with($path, '.mmd')) {
            file_put_contents($path, $diagram);

            return;
        }

        $mermaidBlock = self::START_TAG."\n```mermaid\n{$diagram}```\n".self::END_TAG;

        if (!file_exists($path)) {
            file_put_contents($path, "## ERD\n\n{$mermaidBlock}\n");

            return;
        }

        $content = (string) file_get_contents($path);

        if (str_contains($content, self::START_TAG) && str_contains($content, self::END_TAG)) {
            $pattern = '/'.preg_quote(self::START_TAG, '/').'.*?'.preg_quote(self::END_TAG, '/').'/s';
            // Only fill the first tag pair so later pairs (e.g. a usage example
            // documenting the tags) are left untouched.
            $content = preg_replace_callback($pattern, fn (): string => $mermaidBlock, $content, 1)
                ?? throw new \RuntimeException("Failed to replace the ERD block in {$path}");
        } else {
            $content = rtrim($content)."\n\n## ERD\n\n{$mermaidBlock}\n";
        }

        file_put_contents($path, $content);
    }
}
