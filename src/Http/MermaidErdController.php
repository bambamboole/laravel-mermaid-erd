<?php

declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Http;

use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class MermaidErdController
{
    public function __invoke(MermaidErdGenerator $generator): Response
    {
        $connectionName = config('database.default');
        $cacheKey = "mermaid-erd:diagram:{$connectionName}";

        if (config('mermaid-erd.cache.enabled')) {
            $diagram = Cache::remember(
                $cacheKey,
                config('mermaid-erd.cache.ttl', 3600),
                fn () => $generator->generate(),
            );
        } else {
            $diagram = $generator->generate();
        }

        $escaped = htmlspecialchars($diagram, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Mermaid ERD</title>
        </head>
        <body>
            <pre class="mermaid">{$escaped}</pre>
            <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
        </body>
        </html>
        HTML;

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
